<?php

namespace app\components\daemons;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 *  Класс для управления демонами
 *
 * @todo добавить функцию синхронного запуска демона
 */
class DaemonManager extends Component
{
    /**
     * @var string путь к директории для хранения файлов
     */
    public $dataPath = "@runtime/daemons";

    /**
     * @var string Путь к директории для хранения pid-файлов активных процессов
     *
     *  По умолчанию это поддиректория /pids/ в директории, определенной в $dataPath
     *  Если директория задается в конфиге - она должна существовать, чтобы все заработало.
     */
    public $pidsPath;

    /**
     * @var string Путь к директории для хранения логов демонов
     *
     *  По умолчанию это поддиректория /logs/ в директории, определенной в $dataPath
     *  Логи создаются отдельно для каждого демона, куда поадают потоки STDIN и STDOUT
     *  Если директория задается в конфиге - она должна существовать, чтобы все заработало.
     */
    public $logsPath;

    /**
     * @var array Список конфигураций для демонов в системе
     */
    public $daemons = [];

    public function init()
    {
        parent::init();
        $this->checkConfig();
    }

    /**
     *  Запускает неработающих демонов в соответствии с конфигурацией
     */
    public function start()
    {
        $daemons = $this->getDaemons();
        foreach ($daemons as $daemon) {
            if ($daemon['status'] == false) {
                $this->startDaemon($daemon);
            }
        }
    }

    /**
     *  Ожидает запуска всех сконфигурированных демонов
     * @param int $timeout - максимальное время ожидаения в секундах.
     * @return bool
     */
    public function waitForStart($timeout = 10)
    {
        return $this->waitFor(true, $timeout);
    }

    /**
     *  Останавливает всех работающих демонов
     */
    public function stop()
    {
        $daemons = $this->getDaemons();
        foreach ($daemons as $daemon) {
            if ($daemon['status'] == true) {
                $this->stopDaemon($daemon);
            }
        }
    }

    /**
     *  Ожидает остановки всех сконфигурированных демонов
     * @param int $timeout - максимальное время ожидаения в секундах.
     * @return bool
     */
    public function waitForStop($timeout = 10)
    {
        return $this->waitFor(false, $timeout);
    }

    public function status()
    {
        $daemons = $this->getDaemons();
        foreach ($daemons as $daemon) {
            if ($daemon['pid']) {
                posix_kill($daemon['pid'], SIGUSR1);
            }
        }
        return $daemons;
    }

    /**
     *  Ожидание перехода всех демонов в указанное состояние
     * @param boolean $status ожидаемое состояние
     * @param int $timeout максимальное время ожидания в секундах
     * @return boolean
     */
    protected function waitFor($status, $timeout = 10)
    {
        $max = (int)$timeout;
        for ($i = 0; $i < $max; $i++) {
            $daemons = $this->getDaemons();
            $waitingCounter = 0;
            foreach ($daemons as $daemon) {
                if ($daemon['status'] !== $status) {
                    $waitingCounter++;
                }
            }
            if ($waitingCounter == 0) {
                return true;
            }
            sleep(1);
        }
        return false;
    }

    protected function getDaemons()
    {
        return array_replace_recursive($this->getConfiguredDaemons(), $this->getActiveDaemons());
    }

    /**
     *  Возвращает список работающих демонов на основе имеющихся pid-файлов
     *
     * @return array
     */
    protected function getActiveDaemons()
    {
        $daemons = [];
        $pidFiles = \yii\helpers\FileHelper::findFiles($this->pidsPath, ['only' => ['*.pid']]);
        sort($pidFiles);
        foreach ($pidFiles as $pidFile) {
            $pid = null;
            if (file_exists($pidFile)) {
                $pid = file_get_contents($pidFile);
            }
            list($name, $number) = explode(".", basename($pidFile));
            $status = $pid && posix_kill((int) $pid, 0); // check if process running
            if ($status) {
                $daemons[$name . "." . $number] = [
                    'pid' => $pid,
                    'status' => $status
                ];
            }
        }
        return $daemons;
    }

    /**
     *  Возвращает список демонов на основе конфигурации
     *
     * @return array
     */
    protected function getConfiguredDaemons()
    {
        $daemons = [];
        foreach ($this->daemons as $daemonName => $daemonConfig) {
            $count = ArrayHelper::remove($daemonConfig, 'count', 1);
            for ($i = 0; $i < $count; $i++) {
                $daemonNameNumber = $daemonName . "." . $i;
                $daemon = $daemonConfig;
                $daemon['daemonName'] = $daemonName;
                $daemon['daemonNumber'] = $i;
                $daemon['pidFile'] = $this->dataPath . "/pids/" . $daemonName . "." . $i . ".pid";
                $daemons[$daemonNameNumber] = [
                    'config' => $daemon,
                    'pid' => null,
                    'status' => false,
                ];
            }
        }
        return $daemons;
    }

    /**
     *  Запускает демона на основе его конфигурации
     * @todo нужно проапгрейдить \yii\console\Console::stderr для вывода ошибок в файл
     * @param array $daemon информация о демоне
     * @return void
     */
    protected function startDaemon(array $daemon)
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            // Ошибка
            die('could not fork' . PHP_EOL);
        } elseif ($pid === 0) {
            // Новый процесс, запускаем демона
            // Отцепляем процесс демона от родительского
            posix_setsid();

            // Переопределяем вывод в лог
            fclose(STDIN);
            fclose(STDOUT);
            // fclose(STDERR);
            $logPrefix = $this->logsPath . '/';
            $STDIN = fopen('/dev/null', 'r');
            $STDOUT = fopen($logPrefix . "working.log", 'ab');
            // $STDERR = fopen($logPrefix . "error.log", 'ab');

            // Включаем сообщения об ошибках
            ini_set('display_errors', "stderr");
            ini_set('error_reporting', E_ALL);

            // Создаем демона
            $instance = \Yii::createObject($daemon['config']);
            // Запускаем его
            $instance->run();
            // После окончания работы демона просто умираем
            die;
        } else {
            // Продолжаем выполнение родителя
        }
    }

    /**
     *  Останавливает демона на основе его конфигурации
     * @param array $daemon информация о демоне
     * @return void
     */
    protected function stopDaemon(array $daemon)
    {
        if ($daemon['pid']) {
            posix_kill($daemon['pid'], SIGTERM);
        }
    }

    /**
     *  Метод проверяет конфигурацию и нормализует ее.
     * @throws InvalidConfigException
     * @return void
     */
    protected function checkConfig()
    {
        /// Определяем dataPath
        $this->dataPath = \Yii::getAlias($this->dataPath);
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }

        /// Определяем pidsPath
        $this->pidsPath = \Yii::getAlias($this->pidsPath);
        if (!is_dir($this->pidsPath)) {
            $this->pidsPath = $this->dataPath . "/pids";
            if (!is_dir($this->pidsPath)) {
                mkdir($this->pidsPath, 0755, true);
            }
        }

        /// Определяем logsPath
        $this->logsPath = \Yii::getAlias($this->logsPath);
        if (!is_dir($this->logsPath)) {
            $this->logsPath = $this->dataPath . "/logs";
            if (!is_dir($this->logsPath)) {
                mkdir($this->logsPath, 0755, true);
            }
        }
    }
}

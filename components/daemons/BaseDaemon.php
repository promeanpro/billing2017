<?php

namespace app\components\daemons;

use yii\base\Component;

/**
 *  Компонент для реализации демонов
 */
abstract class BaseDaemon extends Component
{
    /**
     *  @var string Название демона
     */
    public $daemonName;
    /**
     *  @var int Порядковый номер демона
     */
    public $daemonNumber;
    
    /**
     *  @var string путь к файлу с идентификатором процесса
     */
    public $pidFile;
    /**
     *  @var resource Ресурс доступа к файлу с идентификатором процесса.
     *
     *  Используется для предотвращения повторного запуска демона
     */
    private $pidFileHandler;
    
    /**
     *  @var bool Флаг получения сигнала останова
     */
    private $stopSignal = false;
    
    /**
     *  Основной исполняемый метод
     *
     *  Запускается каждую итерацию основного цикла
     *  @return void
     */
    abstract protected function process();
    
    /**
     *  Базовый метод, в котором содерится основной цикл работы
     *  @return void
     */
    public function run()
    {
        try {
            $this->log('starting');
            $this->savePidFile();
            $this->activateSignalListener();
            
            $this->start();
            // Пока не получен сигнал останова
            while (!$this->stopSignal) {
                \Yii::trace('BASE DAEMON WORKS\n');
                // Выполнить работу
                $this->process();
                // Обработать системные сигналы
                pcntl_signal_dispatch();
                $this->checkPidFile();
            }
            $this->stop();
        } catch( \Exception $e ) {
            \Yii::warning($e);
            /// @todo использовать юишные логи
            $this->log("Fatal error #" . $e->getCode() . " " . $e->getMessage());
            $this->log($e->getTraceAsString());
            $this->removePidFile();
            exit(1);
        }
        exit(0); // Успешное зевершение работы
    }
    
    /**
     *  Метод, вызывающийся перед запуском основного цикла работы демона.
     *  @return void
     */
    protected function start()
    {
    }
    
    /**
     *  Метод, вызывающийся по завершению работы демона.
     *  @return void
     */
    protected function stop()
    {
        $this->removePidFile();
        $this->log('stopping');
    }
    
    /**
     *  Создает файл с идентификатором процесса и блокирует его на запись
     *  Если создать файл не удалось - переводит процесс в режим завершения работы.
     *  @return void
     */
    protected function savePidFile()
    {
        $this->pidFileHandler = fopen($this->pidFile, "a+");
        $fp = $this->pidFileHandler;
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            ftruncate($fp, 0);
            fwrite($fp, posix_getpid());
            chmod($this->pidFile, 0666);
        } else {
            $this->log('Can`t start - already running (detecting by file lock)');
            $this->stopSignal = true;
        }
    }
    
    /**
     *  Проверяет наличие и содержимое файла с идентификатором процесса.
     *  Если файл отсутствует или его содержимое отличается от ожидаемого,
     *  переводит процесс в режим завершения работы.
     *  @return void
     */
    protected function checkPidFile()
    {
        if (!is_file($this->pidFile)) {
            $this->log('Pid file check failed - pid file was deleted');
            $this->stopSignal = true;
        } elseif (file_get_contents($this->pidFile) != posix_getpid()) {
            $this->log('Pid file check failed - pid was changed');
            $this->stopSignal = true;
        }
    }
    
    /**
     *  Удаление файла с идентификатором процесса
     *  @param bool $force - удалять ли файл, если в нем другой pid
     *  @return void
     */
    protected function removePidFile($force=false)
    {
        if (is_file($this->pidFile)) {
            if ($force || file_get_contents($this->pidFile) == posix_getpid()) {
                unlink($this->pidFile);
            }
        }
    }
    
    /**
     *  Метод включает обработчики системных сигналов
     *  @return void
     */
    protected function activateSignalListener()
    {
        pcntl_signal(SIGUSR1, [$this, "signalStatusHandler"]);
        pcntl_signal(SIGTERM, [$this, "signalStopHandler"]);
    }
    
    /**
     *  Обработчик сигнала запроса получения статуса демона
     *  @note на данный момент не используется
     *  @return void
     */
    public function signalStatusHandler()
    {
        $this->log('Receive status signal');
    }
    
    /**
     *  Обработчик сигнала останова
     *  @return void
     */
    public function signalStopHandler()
    {
        $this->log('Receive stopping signal');
        $this->stopSignal = true;
    }
    
    /**
     *  Выводит сообщение в STDIN, дополняя его информацией о демоне и времени 
     *
     *  Предполагается, что это сообщение попадет в лог
     *  @todo Переложить на стандартные юишные функции логгирования
     *  @param string $message сообщение к отправке
     *  @return void
     */
    protected function log($message)
    {
        $msg = [
            date('c'),
            $this->daemonName.".".$this->daemonNumber,
            $message
        ];
        echo implode("\t", $msg)."\n";
    }
}

<?php
namespace app\models\commands;

use yii\base\Model;

/**
 * Все команды должны быть наследованы от этого класса
 * Реализует именование команды
 * Обязывает к реализации метод run
 * Class AbstractCommand
 * @package app\models\commands
 */
abstract class AbstractCommand extends Model
{
    public $delegate;

    /**
     * Получить имя команды
     * @return string
     */
    public static function getCommandName() {
        $name = explode('\\', static::className());
        return end($name);
    }

    /**
     * Метод отвечает за выполнение команды
     * @return boolean
     */
    abstract public function run();

    public function setDelegate($delegateClassName)
    {
        $this->delegate = $delegateClassName;
    }
}
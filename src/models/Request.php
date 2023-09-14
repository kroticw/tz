<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $email
 * @property string $phone
 * @property string|null $text
 * @property int|null $manager_id
 *
 * @property Manager|null $manager
 */
class Request extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'requests';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    public function rules()
    {
        return [
            [['email', 'phone'], 'required'],
            ['email', 'email'],
            ['manager_id', 'integer'],
            ['manager_id', 'exist', 'targetClass' => Manager::class, 'targetAttribute' => 'id'],
            [['email', 'phone'], 'string', 'max' => 255],
            ['text', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Добавлен',
            'updated_at' => 'Изменен',
            'email' => 'Email',
            'phone' => 'Номер телефона',
            'manager_id' => 'Ответственный менеджер',
            'text' => 'Текст заявки',
        ];
    }

    public function getManager()
    {
        return $this->hasOne(Manager::class, ['id' => 'manager_id']);
    }

    public static function getRequests($manager_id){ //функция получения всех записей request, у которых id менеджера равно передаваемому
        return Request::find()->where(['manager_id' => $manager_id])->all();
    }

    public function hasDuplic(){  //поиск дубликатов
        //заносим в duplic предыдущую идентичную заявку заявку, но с другим идентификатором 
        $duplic = Request::find()->where(['or', ['phone'=>$this->phone], ['email' => $this->email]])->andWhere(['not in', 'id', $this->id])
                                ->andWhere(['<', 'created_at', $this->created_at])->orderBy(['created_at' => SORT_DESC])->one();
        if(!is_null($duplic)){ //если найдена, то проверяем на время
            $days = (strtotime($this->created_at) - strtotime($duplic->created_at)) / 3600 / 24;
            if($days <= 30)
                return $duplic;
        }
        else
            return null;
    }
}

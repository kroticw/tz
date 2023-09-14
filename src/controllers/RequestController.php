<?php

namespace app\controllers;

use Yii;
use app\models\Manager;
use app\models\Request;
use app\models\RequestSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class RequestController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new RequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Request();

        if($model->load(Yii::$app->request->post()) && $model->save()) {
            $duplic = $model->hasDuplic();
            if(!is_null($duplic) && Manager::IsWorking($duplic->manager_id))
                $model->manager_id = $duplic->manager_id;
            else{
                $query="SELECT m.*,COUNT(r.id) as _count
                        FROM managers as m LEFT JOIN requests as r on m.id = r.manager_id
                        WHERE m.is_works = true
                        GROUP BY m.id
                        ORDER BY _count 
                        LIMIT 1";
                
			    $managers=Yii::$app->db->createCommand($query)->queryAll();
                $model->manager_id = $managers[0]['id'];
                
            }
            if($model->save(false))
                return $this->redirect(['view', 'id' => $model->id]);
        }
        else{
            return $this->render('create', [
                'model' => $model,
            ]);
        }

        /*
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
        */

    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
    
    public function actionPreviosRequest($manager_id){//для первого задания
        $searchModel = new RequestSearch();
        $dataProvider = $searchModel->search(['RequestSearch'=>['manager_id'=>$manager_id]]);
         return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPrevios($id){
        $model = Request::findOne($id);
        $prev = Request::find()->where(['or', ['phone'=>$model->phone], ['email' => $model->email]])
                                ->andWhere(['not in', 'id', $model->id])
                                ->andWhere(['<', 'created_at', $model->created_at])
                                ->orderBy(['created_at' => SORT_DESC])
                                ->one();  
        return $this->render('view', [
            'model' =>$prev,
        ]);

    }

    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

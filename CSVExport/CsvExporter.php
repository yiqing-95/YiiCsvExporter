<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yiqing
 * Date: 12-3-18
 * Time: 上午10:36
 *------------------------------------------------------------
 *                 _            _
 *                (_)          (_)
 *        _   __  __   .--. _  __   _ .--.   .--./)
 *       [ \ [  ][  |/ /'`\' ][  | [ `.-. | / /'`\;
 *        \ '/ /  | || \__/ |  | |  | | | | \ \._//
 *      [\_:  /  [___]\__.; | [___][___||__].',__`
 *       \__.'            |__]             ( ( __))
 *
 *--------------------------------------------------------------
 * To change this template use File | Settings | File Templates.
 */

/**
 * usage exmple:
 * in your admin.php view file
 * <?php
 *        $this->widget('ext.CSVExport.CsvExporter',array(
 *               'cmdLabel'=>'export to c csv file',
 *               'model'=>$model,
 *          //  'gridId'=>'user-grid', //if you use $model this will be ignored
 *      ));
 *      ?>
 *
 */
class CsvExporter extends CWidget
{
    /**
     * @var CActiveRecord
     */
    public $model;
    /**
     * @var string
     */
    public $girdId;

    public $cmdLable = 'Export';

    /**
     * @throws CException
     */
    public function init()
    {
        if (isset($this->model)) {
            //if you give a model name  we just guess the grid id well be following wich is same as the gii generated for you admin view ;
            $this->girdId = $this->class2id(get_class($this->model)) . '-grid';
        }
        if (empty($this->girdId)) {
            throw new CException('you must specify a gridId for using this widget');
        }
        parent::init();
    }

    public function run()
    {
        echo CHtml::button($this->cmdLable,
            array('id' => 'export-button',
                'class' => 'span-3 button',
                'title' => $this->girdId,
                'onclick' => 'CsvExporter.export(this)'
            ));

        $cs = Yii::app()->getClientScript();
        $cs->registerScript(__CLASS__, $this->jsCode(), CClientScript::POS_END);
    }

    /**
     * @return string
     */
    public function jsCode()
    {
        $iframeId = __CLASS__.'_tempFrame';
        $js = <<<CODE
 var CsvExporter = {
        counter:0,
        export:function (element) {
            var \$link = $(element),
                gridId = \$link.attr('title'),
                url = $.fn.yiiGridView.getUrl(gridId);
            var \$tempFrame = $("#{$iframeId}");
            if (\$tempFrame.length < 1) {
                //alert("first creat the iframe");
                \$tempFrame = $("<iframe id='{$iframeId}'  ></iframe>");
                \$tempFrame.appendTo('body');
            }
            CsvExporter.counter = CsvExporter.counter + 1;
            url = $.param.querystring(url,
                {"counter":CsvExporter.counter,
                    'act':'exportCsv'
                }
            );
            \$tempFrame.attr('src', url);
            \$tempFrame.css('display','none');
            // window.location = url;
            // window.location.href = url;
        }
    };
CODE;
        return $js;
    }

    /**
     * @param $name
     * @return string
     */
    protected function class2id($name)
    {
        return trim(strtolower(str_replace('_', '-', preg_replace('/(?<![A-Z])[A-Z]/', '-\0', $name))), '-');
    }

    //--------------------------below is for controller ----------------------------------------------------------------
    /**
     * useage example (normally in your controller::actionAdmin method ):
     *  public function actionAdmin()
     *           {
     *                    $model = new UserProfile('search');
     *                    $model->unsetAttributes();
     *
     *               if (isset($_GET['UserProfile']))
     *                 $model->attributes = $_GET['UserProfile'];
     *
     *               if(isset($_GET['act'])){
     *                  Yii::import('ext.CSVExport.CsvExporter');
     *                  CsvExporter::fileName('anyName.cvs');
     *                  CsvExporter::export($model);
     *               }
     *
     *               $this->render('admin', array(
     *               'model' => $model,
     *               ));
     *           }
     *
     */
    /**
     * @var string
     */
    static public $actionKey = 'act';
    /**
     * @var string
     */
    static public $actionValue = 'exportCsv';

    /**
     * @var string
     * the download file name
     */
    static public $fileName = 'export.csv';

    /**
     * @var array
     * for the underlying CSVExport object
     */
    static public $options = array();

    /**
     * @static
     * @param CActiveRecord $model
     * @param array|string $columns can also passed from js ,if so you can use $_GET to retrive it
     *          array('id','name') | 'id,name' 或者带别名 't.id,user.name....'
     * @param array $headers
     */
    static public function export(CActiveRecord $model, $columns = array(), $headers = array())
    {
        if (isset($_GET[self::$actionKey])) {

            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CSVExport.php');

            $provider = $model->search();

            if (!empty($columns)) {
                $criteria = $provider->getCriteria();
                $criteria->select = $columns;
            }
            $csv = new CSVExport($provider);

            if (!empty($headers)) {
                $csv->headers = $headers;
            }
            $csv->headers = $model->attributeLabels();
            $csv->exportFull = false ; //default use pagination
            if(!empty(self::$options)){
                foreach(self::$options as $key=>$value)
                    $csv->$key=$value;
            }
            //  echo var_export($provider->getCriteria()->toArray(),true);

            $content = $csv->toCSV(null, "\t", '"');
            Yii::app()->getRequest()->sendFile(self::$fileName, $content, "text/csv", false);
            die();//  exit;
        }
        /*
        else{
           $actionKey  = self::$actionKey;
            throw new CException("something wrog with your request,must contain the key '{$actionKey}' your get is: ".CJSON::encode($_GET) );
        }*/
    }
    //------------------------------------------------------------------------------------------------
}

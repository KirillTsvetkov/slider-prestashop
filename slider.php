<?php 
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Slider extends Module implements WidgetInterface
{

    private $templateFile;

    public function __construct()
    {
        $this->name = 'slider';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Kirill Tsvetkov';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Slider');
        $this->description = $this->l('Установка слайдера');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->templateFile = 'module:slider/views/templates/hook/slider.tpl';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."slider` (
            `id_slide` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `url` varchar(255) NOT NULL,
            `status` boolean NOT NULL,
            PRIMARY KEY (`id_slide`)) ";
        if(!$result=Db::getInstance()->Execute($sql)){
            return false;
        }
        
        return parent::install()&&
            $this->registerHook('displayHome')&&
            $this->registerHook('header');
    }

    public function uninstall()
    {
        $sql = "DROP TABLE `"._DB_PREFIX_."slider`";
        if(!$result=Db::getInstance()->Execute($sql)){
            return false;
        }
        return parent::uninstall();
    }
    
    public function hookHeader()
    {
        $this->context->controller->addJS(($this->_path).'/js/slider.js');
        $this->context->controller->addCSS(($this->_path).'/css/slider.css');

    }
    

    public function renderWidget($hookName, array $configuration)
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        return $this->fetch($this->templateFile);   
    }

    public function getWidgetVariables($hookName, array $configuration)
    {

        $sql = "SELECT * FROM `"._DB_PREFIX_."slider` WHERE status = 1";
        $result = Db::getInstance()->executeS($sql);
        return array(
            'imgs' => $result

        );
    } 
   
    protected function postProcess()
    {
       
        $id = Tools::getValue('id');
        $slidename = Tools::getValue('slidename');
        $active = Tools::getValue('active');
        $sliderfile = Tools::getValue('sliderfile');
        var_dump($sliderfile);
        $target_file = _PS_ROOT_DIR_.'\modules\slider\img\\'.$_FILES["sliderfile"]["name"];
        if($sliderfile!==""){
            if(!move_uploaded_file($_FILES["sliderfile"]["tmp_name"], $target_file)){
                echo 'error  '.$target_file;
            }
        }

        
        if($id==""){
            $sql ="insert into `"._DB_PREFIX_."slider` 
            values (null, '".$slidename."', '".$_FILES["sliderfile"]["name"]."', '".$active."')";
            
            if(!$result=Db::getInstance()->Execute($sql)){
                return false;
            }
        }
        else{
            if($_FILES["sliderfile"]["tmp_name"]){
                $sliderfile = $_FILES["sliderfile"]["name"];
                $url = " url ='".$sliderfile."',";
            }
            else
            {
                $url = "";
            }
            $sql ="update `"._DB_PREFIX_."slider` 
            set".$url." name = '".$slidename."',status='".$active."' where id_slide =".$id;
            echo $sql;
            if(!$result=Db::getInstance()->Execute($sql)){
                return false;
            }
        }
        
        return true;
        

    }
  
  

    public function getContent()
    {
        if (Tools::isSubmit('id_slide')) {
            if(isset($_GET['deleteslider'])){
                echo 'Удаление';
            }
            else{
                echo 'Не удаление';
                $id_slide = $_GET['id_slide'];
                $sql = "SELECT * FROM `"._DB_PREFIX_."slider` WHERE id_slide =".$id_slide;
                $result=Db::getInstance()->ExecuteS($sql);
                $name = $result[0]['name'];
                $url = $result[0]['url'];
                $status = $result[0]['status'];
                return $this->renderForm($name, $status, $id_slide, $url);
            }
            
        }
        if (((bool)Tools::isSubmit('submitSlider')) == true) {
            $this->postProcess();
        }
        
        return $this->renderForm();
    }



    protected function renderForm($slidename = null, $status=null, $id_slide=null, $url=null)
    {
        if($url){
            $image_url ="/modules/slider/img/".$url;
            $img = '<div class="col-lg-6"><img src="'.$image_url.'" class="img-thumbnail" width="400"></div>';
        }else{
            $img = '';
        }
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlider';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        $helper->fields_value = array(
            'slidename' => $slidename,
            'active'=>$status,
            'id'=>$id_slide
       );
        $_html = $helper->generateForm(array($this->getConfigForm($img)));
        $_html .= $this->renderList();

        return $_html;
    }

  
    protected function getConfigForm($img=null)
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Настройки'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'name' => 'sliderfile',
                        'label' => $this->l('Выберите файл'),
                        'image' => $img
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'slidename',
                        'label' => $this->l('Введите название слайда'),                      
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'id',
                    ),
                    array(
                    'type'      => 'radio',                             
                    'label'     => $this->l('Статус слайда'),        
                    'name'      => 'active',                             
                    'required'  => true,                                 
                    'class'     => 't',                                  
                    'is_bool'   => true,                                  
                    'values'    => array(               
                        array(
                        'id'    => 'active_on',                           
                        'value' => 1,                                      
                        'label' => $this->l('Активен')                    
                        ),
                        array(
                        'id'    => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Отключен')
                        )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function renderList()
    {
        $db = Db::getInstance();
        $sql='SELECT * FROM ' . _DB_PREFIX_ . 'slider';
        $results=$db->ExecuteS($sql);
        $this->fields_list = array(
            'id_slide' => array(
                'title' => $this->l('Id'),
                'width' => 140,
                'type' => 'text'
            ),
            'name' => array(
                'title' => $this->l('name'),
                'width' => 140,
                'type' => 'text'
            ),
            'url' => array(
                'title' => $this->l('url'),
                'width' => 'auto',
                'type' => 'text'
            ),
            'status' => array(
                    'title' => $this->l('status'),
                    'width' => 140,
                    'type' => 'text'
            )
        );
        $helper= new HelperList();
        $helper->simple_header = true;
        $helper->listTotal = count($results);
        $helper->actions = array('edit', 'delete', 'view','test');
        $helper->module = $this;
        $helper->identifier = 'id_slide';
        $helper->show_toolbar = true;
        $helper->title = 'Список слайдов';
        $helper->table = 'slider';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars['token'] = $helper->token;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        
        return $helper->generateList($results,$this->fields_list);
    }

    protected function test(){}

}
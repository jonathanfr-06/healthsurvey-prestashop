<?php

/**
 * 2007-2023 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2023 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Healthsurvey extends Module
{     
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'healthsurvey';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Jonathan FRANCO';
        $this->need_instance = 1;
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Health Survey');
        $this->description = $this->l('Use this module if you need information about your customers health. You can add as many questions as you want.');
        $this->confirmUninstall = $this->l('');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('HEALTH_SURVEY_LIVE_MODE', false);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('displayHeader')&&
            $this->registerHook('displayLeftColumn')&&
            $this->registerHook('displayTopPayment')&&
            $this->registerHook('displayBackOfficeHeader')&&
            $this->registerHook('displayCheckoutSummaryTop')&&
            $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        Configuration::deleteByName('HEALTH_SURVEY_LIVE_MODE');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitHealth_surveyModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->displayForm();
        $output .= $this->displayQuestions();
        $output .= $this->displayAnswers();
        return $output;
    }
    public function displayForm()
    {
        $this->postProcess();
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Add a question'),
                        'name' => 'question',
                        'size' => 255,
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l("Submit"),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        // Module, token and currentIndex
        $helper->table = _DB_PREFIX_ . 'healthSurveyQuestions';
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        return $helper->generateForm([$form]);
    }
    /**
     * Create the structure of your form.
     */
    public function displayQuestions()
    {
        $this->postProcess();
        $fields_list = array(
            'id' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'question' => array(
                'title' => $this->l('Question'),
                'align' => 'center',
            ),
            'delete' => array(
                'title' => $this->l(''),
                'align' => 'right',
                'class' => 'fixed-width-sm',
                'type' => 'button',
                'icon' => 'icon-trash',
                'js' => 'if (confirm(\'' . $this->l('Are you sure you want to delete this question?') . '\')) deleteQuestion(' . $question['id'] . ', \'' . Tools::safeOutput($question['question']) . '\', \'deletehealthSurveyQuestions\')',
            ),
        );
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id';
        $helper->table = 'healthSurveyQuestions';
        $helper->actions = array('delete');
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->title = $this->l('Health Survey Questions');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);

        $questions = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'healthSurveyQuestions');

        return $helper->generateList($questions, $fields_list);
    }

    public function displayAnswers()
{
    $this->postProcess();
    $fields_list = array(
        'id' => array(
            'title' => $this->l('ID'),
            'align' => 'center',
            'class' => 'fixed-width-xs',
        ),
        'question' => array(
            'title' => $this->l('Question'),
            'align' => 'center',
        ),
        'answer' => array(
            'title' => $this->l('Answer'),
            'align' => 'center',
        ),
        'email_customer' => array(
            'title' => $this->l('Email'),
            'align' => 'center',
        ),
    );

    $helper = new HelperList();
    $helper->shopLinkType = '';
    $helper->simple_header = true;
    $helper->identifier = 'id';
    $helper->table = 'healthSurveyAnswers';
    $helper->show_toolbar = false;
    $helper->module = $this;
    $helper->title = $this->l('Health Survey Answers');
    $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);

    $query = new DbQuery();
    $query->select('a.id, q.question, a.answer, a.email_customer');
    $query->from('healthSurveyAnswers', 'a');
    $query->leftJoin('healthSurveyQuestions', 'q', 'a.id_question = q.id');
    $query->orderBy('a.email_customer ASC'); // order by customer email in ascending order
    $questions = Db::getInstance()->executeS($query);

    return $helper->generateList($questions, $fields_list);
}

 
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'HEALTH_SURVEY_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'HEALTH_SURVEY_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'HEALTH_SURVEY_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        return array(
            'HEALTH_SURVEY_LIVE_MODE' => Configuration::get('HEALTH_SURVEY_LIVE_MODE', true),
            'HEALTH_SURVEY_ACCOUNT_EMAIL' => Configuration::get('HEALTH_SURVEY_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'HEALTH_SURVEY_ACCOUNT_PASSWORD' => Configuration::get('HEALTH_SURVEY_ACCOUNT_PASSWORD', null),
        );
    }

   //This will handle all PostRelated functions
    protected function postProcess()
    {
        if (((bool)Tools::isSubmit('submithealthsurvey')) == true) {
            $this->newQuestion();
        }
        if (Tools::isSubmit('deletehealthSurveyQuestions')) {
            $this->deleteQuestion();
        }
        if (Tools::isSubmit('survey-answers')) {
             $this->newAnswers();
        }
}

    public function newQuestion(){
        $submittedQuestion = $_POST['question'];
            $existing_question = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'healthSurveyQuestions WHERE question = "'.$submittedQuestion.'"');
            if($existing_question > 0){
                return;
            }
            if (!empty($submittedQuestion)) {
                Db::getInstance()->insert('healthSurveyQuestions', array(
                    'question' => $submittedQuestion,
                ));
            }
    }

    public function newAnswers(){
        $questions = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'healthSurveyQuestions');
        $existing_email = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'healthSurveyAnswers WHERE email_customer = "'.$this->context->customer->email.'"');
        if ($existing_email > 0) {
            $this->getContent();
        }
        foreach ($questions as $question) {
            $question_id = (int) $question['id'];
            $answer = Tools::getValue('answer_'.$question['id']);
            Db::getInstance()->insert('healthSurveyAnswers', array(
                'id_question' => $question_id,
                'answer'=> $answer,
                'email_customer'=>$this->context->customer->email
            ));
    }
       return;
    }
    public function deleteQuestion()
    {
        $id_health_survey_question = (int)Tools::getValue('id');
        if ($id_health_survey_question > 0) {
            Db::getInstance()->delete('healthSurveyQuestions', 'id=' . (int)$id_health_survey_question);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
        }
        return false;
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookDisplayCheckoutSummaryTop()
    {
        $this->postProcess();
        $this->context->smarty->assign([
            'my_module_name' => Configuration::get('MYMODULE_NAME'),
            'questions' =>Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'healthSurveyQuestions'),
        ]);
        return $this->display(__FILE__, 'healthsurvey.tpl');
    }

    public function hookDisplayLeftColumn()
    {
        $this->postProcess();
        $this->context->smarty->assign([
            'my_module_name' => Configuration::get('MYMODULE_NAME'),
            'questions' =>Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'healthSurveyQuestions'),
        ]);
        return $this->display(__FILE__, 'healthsurvey.tpl');
    }
    public function hookDisplayHeader($params)
    {
            // $existing_email = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'healthSurveyAnswers WHERE email_customer = "'.$this->context->customer->email.'"');
            // if($existing_email > 0 || !$this->context->customer->email)
            // {
            // return;
            // }
            // else
            // {
              $this->postProcess();
              $this->context->smarty->assign([
                    'my_module_name' => Configuration::get('MYMODULE_NAME'),
                    'questions' =>Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'healthSurveyQuestions'),
        ]);
              return $this->display(__FILE__, 'healthsurvey.tpl');
    }
    public function hookDisplayTopPayment()
    {
        $this->postProcess();
        $this->context->smarty->assign([
            'my_module_name' => Configuration::get('MYMODULE_NAME'),
            'questions' =>Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'healthSurveyQuestions'),
        ]);
        return $this->display(__FILE__, 'healthsurvey.tpl');
    }
    public function hookActionFrontControllerSetMedia()
    {
    $this->context->controller->registerStylesheet(
        'mymodule-style',
        $this->_path.'views/css/mymodule.css',
        [
            'media' => 'all',
            'priority' => 1000,
        ]
    );

    $this->context->controller->registerJavascript(
        'mymodule-javascript',
        $this->_path.'views/js/mymodule.js',
        [
            'position' => 'bottom',
            'priority' => 1000,
        ]
    );
    }
}

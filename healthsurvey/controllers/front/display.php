<?php
class HealthSurveydisplayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $questions = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'healthSurveyQuestions');
        $this->context->smarty->assign(array(
            'questions' => $questions,
        ));
        $this->setTemplate('module:healthsurvey/views/templates/front/display.tpl');
    }
    public function postProcess()
    {
        $customer_email = $this->context->customer->email;
        $answers =  Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'healthSurveyQuestions ' . 'WHERE email_customer=' . $customer_email);
        if (Tools::isSubmit('survey-answers')) {
            die($answers);
            foreach ($questions as $question) {
                $question_id = (int) $question['id'];
                $answer = Tools::getValue('answer');
                Db::getInstance()->insert('healthSurveyAnswers', array(
                    'id_question' => $question_id,
                    'answer'=> $answer,
                    'email_customer'=>$customer_email
                ));
        }
    }}
}
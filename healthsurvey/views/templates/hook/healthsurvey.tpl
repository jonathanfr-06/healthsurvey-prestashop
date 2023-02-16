<div id="mymodule_block_home" class="block">
  <h4>{l s='Health Survey - Feel free to complete the form' mod='health_survey'}</h4>
  <div class="block_content">
    <form action="{url entity=$smarty.get.controller}" method="post">
      <ul>
        {foreach $questions as $question}
          <li>
            <label>{$question.question}</label><br>
              <input type="text" name="answer_{$question.id}" value="" required='true'>
          </li>
        {/foreach}
      </ul>
      <input type="submit" name="survey-answers" value="{l s='Submit'}">
    </form>
  </div>
</div>




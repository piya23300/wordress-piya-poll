<?php 

/**
* 
*/
class Poll {

  private $_POST;
  public $topic;
  public $choices;
  
  function __construct($post_data) {
    $this->_POST = $post_data;
    $this->init_poll();
  }

  public function send_poll() {
    $url = 'http://0.0.0.0:3000/plugins/surveys.json';
    
    $send_attrs = array(
      'headers' => array(
        'Content-Type' => 'application/json; charset=utf-8',
      ),
      'body' => json_encode( $this->poll_attrs() )
    );
    $response = wp_remote_post( $url, $send_attrs )['body'];
    return $response;
  }

  public function poll_attrs() {
    $poll_attrs = array(
      'survey' => array(
        'name' => $this->topic,
        'questions_attributes' => array(
          array(
            'title' => $this->topic,
            'answers_attributes' => $this->answers_array()
          )
        )
      )
    );
    return $poll_attrs;
  }

  private function init_poll() {
    $this->topic = $_POST['piya_topic'];
    $this->choices = $_POST['piya_choice_group'];
  }


  private function answers_array() {
    $answer = array();
    foreach ($this->choices as $order=>$choice) {
      array_push($answer, array('text' => $choice['choice'], 'order' => $order+1) );
    }
    return $answer;
  }

}


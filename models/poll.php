<?php 

/**
* 
*/
class Poll {

  private $_POST;
  public $id;
  public $question_id;
  public $topic;
  public $choices;
  private $secret_key;
  
  function __construct($post_data) {
    $this->_POST = $post_data;
    $this->init_poll();
    $this->secret_key = "i-kTxpCaB8Zz85ptSAlXaw";
  }

  public function create() {
    $url = 'http://0.0.0.0:3000/api/v1/surveys';
    
    $send_attrs = array(
      'headers' => array(
        'Content-Type' => 'application/json; charset=utf-8',
      ),
      'body' => json_encode( $this->poll_attrs() )
    );
    $response = wp_remote_retrieve_body( wp_remote_post( $url, $send_attrs ) );
    return json_decode($response);
  }

  public function update() {
    echo 'updateeeeeeeeee';
    $url = 'http://0.0.0.0:3000/api/v1/surveys/' . $this->id;
    
    $send_attrs = array(
      'method' => 'PUT',
      'headers' => array(
        'Content-Type' => 'application/json; charset=utf-8',
      ),
      'body' => json_encode( $this->poll_attrs() )
    );
    // print_r( $send_attrs );
    // exit();
    $response = wp_remote_retrieve_body( wp_remote_post( $url, $send_attrs ) );
    return json_decode($response);
  }

  public function poll_attrs() {
    $poll_attrs = array(
      'key' => $this->secret_key,
      'survey' => array(
        'id' => $this->id,
        'name' => $this->topic,
        'questions_attributes' => array(
          array(
            'id' => $this->question_id,
            'title' => $this->topic,
            'order' => 1,
            'answers_attributes' => $this->answers_array()
          )
        )
      )
    );
    return $poll_attrs;
  }

  private function init_poll() {
    $this->id = $_POST['piya_poll_id'];
    $this->question_id = $_POST['piya_question_id'];
    $this->topic = $_POST['piya_topic'];
    $this->choices = $_POST['piya_choice_group'];
  }


  private function answers_array() {
    $answers = array();
    print_r( $this->choices );
    echo "\n=============\n";
    foreach ($this->choices as $order=>$choice) {
      array_push($answers, array(
        'id' => $choice['answer_id'],
        'text' => $choice['choice'], 
        'order' => $order+1
      ) );
    }
    print_r( $answers );
    exit();
    return $answers;
  }

}


<?php 

/**
* 
*/
class Poll {

  private static $request_domain = 'http://0.0.0.0:3000/api/v1';
  private $post_data;
  public $post_id;
  public $id;
  public $question_id;
  public $topic;
  public $choices;
  public $blog_url;
  public $blog_status;
  private $secret_key;
  
  function __construct( $post_id, $secret_key ) {
    $this->post_id = $post_id;
    $this->init_poll();
    $this->secret_key = $secret_key;
  }

  public function create( $attrs ) {
    if( empty( $this->secret_key ) ) return (object)array( 'error' => 401,  'message' => 'key not present');
    if( empty( $this->topic ) ) return (object)array( 'error' => 422, 'message' => 'skiped create or update topic because topic not present');
    
    $url = self::$request_domain . '/surveys';
    $this->permit_poll($attrs);

    $send_attrs = array(
      'headers' => array(
        'Content-Type' => 'application/json; charset=utf-8',
      ),
      'body' => json_encode( $this->poll_attrs() )
    );

    $response = wp_remote_retrieve_body( wp_remote_post( $url, $send_attrs ) );  
    return json_decode($response);
  }

  public function update( $attrs ) {
    if( empty( $this->secret_key ) ) return (object)array( 'error' => 401,  'message' => 'key not present');
    if( empty( $this->topic ) ) return (object)array( 'error' => 422, 'message' => 'skiped create or update topic because topic not present');
    
    $url = self::$request_domain . '/surveys/' . $this->id;
    $this->permit_poll($attrs);

    $send_attrs = array(
      'method' => 'PUT',
      'headers' => array(
        'Content-Type' => 'application/json; charset=utf-8',
      ),
      'body' => json_encode( $this->poll_attrs() )
    );
    $response = wp_remote_retrieve_body( wp_remote_post( $url, $send_attrs ) );
    return json_decode($response);
  }

  public function poll_attrs() {
    $poll_attrs = array(
      'key' => $this->secret_key,
      'survey' => array(
        'id' => $this->id,
        'name' => $this->topic,
        'blog_status' => $this->generate_status( $this->blog_status ),
        'blog_url' => $this->blog_url,
        'questions_attributes' => array(
          array(
            'id' => $this->question_id,
            'title' => $this->topic,
            'position' => 1,
            'answers_attributes' => $this->answers_array()
          )
        )
      )
    );

    return $poll_attrs;
  }

  private function init_poll() {
    $metabox = 'piya_poll_metabox';
    $this->id = cmb2_get_field_value( $metabox, 'piya_poll_id', $this->post_id );
    $this->question_id = cmb2_get_field_value( $metabox, 'piya_question_id', $this->post_id );
    $this->topic = cmb2_get_field_value( $metabox, 'piya_topic', $this->post_id );
    $this->choices = cmb2_get_field( $metabox, 'piya_choice_group', $this->post_id )->value;
    $this->blog_status = get_post_status( $this->post_id );
    $this->blog_url = wp_get_shortlink( $this->post_id ); 
  }

  private function permit_poll( $attrs ) {
    if( !empty( $attrs ) ) {
      $this->set_attr('post_data', $attrs);
      $this->set_attr('id', $attrs['piya_poll_id']);
      $this->set_attr('question_id', $attrs['piya_question_id']);
      $this->set_attr('topic', $attrs['piya_topic']);
      $this->set_attr('choices', $attrs['piya_choice_group']);
      $this->set_attr('blog_status', $attrs['post_status']);
      $this->set_attr('blog_url', wp_get_shortlink( $this->post_id ) );
    }
  }

  private function set_attr( $attr_name, $value ) {
    if( !empty( $value ) ) {
      $this->$attr_name = $value;
    }
  }


  private function answers_array() {
    $answers = array();
    foreach ($this->choices as $position => $choice) {
      array_push($answers, array(
        'id' => $choice['answer_id'],
        'text' => $choice['choice'], 
        'position' => $position+1
      ) );
    }
    return $answers;
  }

  private function generate_status( $status ) {
    $deleted = ['trash'];
    $draft = ['new', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'];
    $published = ['publish'];
    
    switch ( $status ) {
      case ( in_array( $status, $published ) ):
        $status = 'published';
        break;
      case ( in_array( $status, $draft ) ):
        $status = 'draft';
        break;
      case ( in_array( $status, $deleted ) ):
        $status = 'deleted';
        break;
      default:
        $status = 'draft';
        break;
    }
    
    return $status;    
  }

}


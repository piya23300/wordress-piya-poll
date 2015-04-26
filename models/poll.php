<?php 

/**
* 
*/
class Poll {

  private static $request_domain = 'http://0.0.0.0:3000/api/v1';
  private $post_data;
  public $post_id;
  public $metabox_id = 'piya_poll_metabox';
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
    $this->id = cmb2_get_field_value( $this->metabox_id, 'piya_poll_id', $this->post_id );
    $this->question_id = cmb2_get_field_value( $this->metabox_id, 'piya_question_id', $this->post_id );
    $this->topic = cmb2_get_field_value( $this->metabox_id, 'piya_topic', $this->post_id );
    $this->choices = cmb2_get_field( $this->metabox_id, 'piya_choice_group', $this->post_id )->value;
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

    # update or create answers
    foreach ($this->choices as $position => $choice) {
      array_push($answers, array(
        'id' => $choice['answer_id'],
        'text' => $choice['choice'], 
        'position' => $position+1,
        '_destroy' => 0
      ) );
    }

    # destroy answers
    $destory_answers = array();
    array_push( $destory_answers, $this->answers_was_deleted() );
    foreach ($destory_answers as $choice) {
      array_push($answers, array(
        'id' => $choice['answer_id'],
        'text' => $choice['choice'], 
        '_destroy' => 1
      ) );
    }
    return $answers;
  }

  private function answers_was_deleted() {
    $old_choices = cmb2_get_field( $this->metabox_id, 'piya_choice_group', $this->post_id )->value;
    $new_choices = $this->choices;
    foreach ($old_choices as $old_choice) {
      $response = $this->search( $new_choices, 'answer_id', $old_choice['answer_id']);
      if( $response == null ) {
        return $old_choice;
      }
    }
    return;
  }

  private function search($array, $key, $value) {
    if (is_array($array)) {
      if (isset($array[$key]) && $array[$key] == $value) {
        return $array;
      }

      foreach ($array as $subarray) {
        $rs = $this->search($subarray, $key, $value);
        if($rs!=null) return $subarray;
      }
    }
    return null;
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


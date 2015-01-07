<?php

require_once APP_PATH.'third_party/pbkdf2.php';
ini_set('session.use_cookies', '0');
session_cache_limiter('');

class Hm_Test_Session extends PHPUnit_Framework_TestCase {

    private $config;

    /* set things up */
    public function setUp() {
        $this->config = new Hm_Mock_Config();
        setup_db($this->config);
    }

    /* tests for Hm_PHP_Session */
    public function test_build_fingerprint() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $this->assertEquals('f60ed56a9c8275894022fe5a7a1625c33bdb55b729bb4e38962af4d1613eda25', $session->build_fingerprint($request));
    }
    public function test_record_unsaved() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $session->record_unsaved('test');
        $this->assertEquals(array('test'), $session->get('changed_settings'));
    }
    public function test_is_active() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $this->assertFalse($session->is_active());
    }
    public function test_check() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->check($request, 'unittestuser', 'unittestpass');
        $this->assertFalse($session->is_active());
        $session->destroy($request);
        $request->cookie['PHPSESSID'] = 'testid';
        $request->invalid_input_detected = true;
        $request->invalid_input_fields = array('test');
        $session->check($request);
    }
    public function test_check_fingerprint() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->check($request, 'unittestuser', 'unittestpass');
        $this->assertFalse($session->is_active());
        $session->check_fingerprint($request);
        $this->assertFalse($session->is_active());
        $session->destroy($request);
    }
    public function test_change_pass() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $this->assertFalse($session->change_pass('unittestuser', 'unittestpass'));
    }
    public function test_create() {
        /* TODO: assertions */
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->create($request, 'unittestuser', 'unittestpass');
        $session->destroy($request);
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_None');
        $session->create($request, 'unittestuser', 'unittestpass');
        $session->destroy($request);
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_None');
        $session->create($request, 'unittestuser', 'unittestpass');
    }
    public function test_start() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->start($request);
        $this->assertTrue($session->is_active());
        $request->invalid_input_detected = true;
        $request->invalid_input_fields = array('test');
        $session->check($request);
        $session->destroy($request);
    }
    public function test_session_params() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $this->assertEquals(array(false, false, 'test'), $session->set_session_params($request));
        $request->tls = true;
        $request->path = 'test';
        $this->assertEquals(array(true, 'test', 'test'), $session->set_session_params($request));
    }
    public function test_get_and_set() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $this->assertFalse($session->get('test'));
        $session->set('test', 'testvalue');
        $this->assertEquals('testvalue', $session->get('test'));
        $this->assertFalse($session->get('usertest', false, true));
        $session->set('usertest', 'uservalue', true);
        $this->assertEquals('uservalue', $session->get('usertest', false, true));
    }
    public function test_del() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $session->set('test', 'testvalue');
        $this->assertEquals('testvalue', $session->get('test'));
        $session->del('test');
        $this->assertFalse($session->get('test'));
    }
    public function test_end() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->start($request);
        $this->assertTrue($session->is_active());
        $session->end();
        $this->assertFalse($session->is_active());
    }
    public function test_close_early() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $session->set('test', 'testvalue');
        $session->close_early();
        $this->assertFalse($session->is_active());
    }
    public function test_save_data() {
        $session = new Hm_PHP_Session($this->config, 'Hm_Auth_DB');
        $session->set('test', 'testvalue');
        $session->save_data();
        $this->assertEquals(array(), $_SESSION);
        $request = new Hm_Mock_Request('HTTP');
        $session->destroy($request);
    }

    /* tests for Hm_DB_Session */
    public function test_db_connect() {
        $_POST['user'] = 'unittestusers';
        $_POST['pass'] = 'unittestpass';
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $this->assertTrue($session->connect());
        $config = new Hm_Mock_Config();
        $session = new Hm_DB_Session($config, 'Hm_Auth_DB');
        $this->assertFalse($session->connect());
    }
    public function test_db_end() {
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->end();
        $this->assertFalse($session->is_active());
    }
    public function test_db_start() {
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->loaded = true;
        $session->start($request);
        $this->assertTrue($session->is_active());
        $session->destroy($request);

        $request->cookie['hm_session'] = 'test';
        $session->loaded = false;
        $session->start($request);
        $this->assertFalse($session->is_active());
        $session->destroy($request);

        $request->cookie = array();
        $session->loaded = false;
        $session->start($request);
        $this->assertFalse($session->is_active());
        $session->destroy($request);
    }
    public function test_db_close_early() {
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->close_early();
        $this->assertFalse($session->is_active());
    }
    public function test_db_save_data() {
        /* TODO: assertions */
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $session->loaded = true;
        $session->start($request);
        $session->save_data();
    }
    public function test_plaintext() {
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $this->assertEquals(array('data'), ($session->plaintext($session->ciphertext(array('data')))));
    }
    public function test_get_key() {
        /* TODO: assertions */
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $request->cookie['hm_id'] = 'test';
        $session->get_key($request);
    }
    public function test_secure_cookie() {
        /* TODO: assertions */
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_DB');
        $request = new Hm_Mock_Request('HTTP');
        $request->tls = true;
        $request->path = 'test';
        $session->secure_cookie($request, 'name', 'value');
    }
    public function test_insert_session_row() {
        $request = new Hm_Mock_Request('HTTP');
        $config = new Hm_Mock_Config();
        $session = new Hm_DB_Session($this->config, 'Hm_Auth_None');
        $this->assertFalse($session->insert_session_row());
        $session->connect();
        $this->assertFalse($session->insert_session_row());
    }
}

?>

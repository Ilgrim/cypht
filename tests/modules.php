<?php

require '../modules/core/setup.php';
require '../modules/core/modules.php';

class Hm_Handler_Test extends Hm_Handler_Module {
    public function process() {
        return true;
    }
}
class Hm_Output_Test extends Hm_Output_Module {
    public function output($format) {
        return '';
    }
}

class Hm_Test_Modules extends PHPUnit_Framework_TestCase {
    private $output_mod;
    private $handler_mod;
    private $parent;
    private $request_handler;

    /* test for the Hm_Module_Output trait */
    public function setUp() {
        Hm_Handler_Modules::add('test', 'date', false, false, 'after', true, 'core');
        $parent = new stdClass();
        $parent->session = new Hm_Mock_Session();
        $parent->request = new Hm_Mock_Request('HTML5');
        $parent->config = new Hm_Mock_Config();
        $parent->user_config = new Hm_Mock_Config();
        $this->parent = $parent;

        $this->handler_mod = new Hm_Handler_Test($parent, false);
        $this->output_mod = new Hm_Output_Test(array('foo' => 'bar', 'bar' => 'foo'), array('bar'));
        $this->request_handler = new Hm_Request_Handler();
    }
    public function test_out() {
        $this->assertTrue($this->handler_mod->out('foo', 'bar'));
        $this->assertFalse($this->handler_mod->out('foo', 'foo'));
    }
    public function test_append() {
        $this->assertTrue($this->handler_mod->append('test', 'value'));
        $this->assertTrue($this->handler_mod->append('test', 'value'));
        $this->assertEquals(array('value', 'value'), $this->handler_mod->get('test'));
        $this->handler_mod->out('no_append', 'blah', true);
        $this->assertFalse($this->handler_mod->append('no_append', 'blah'));
    }
    public function test_concat() {
        $this->assertTrue($this->handler_mod->concat('concat', 'start'));
        $this->assertTrue($this->handler_mod->concat('concat', 'start'));
        $this->assertEquals('startstart', $this->handler_mod->get('concat'));
    }
    public function test_module_output() {
        $this->handler_mod->out('foo', 'bar');
        $this->assertEquals(array('foo' => 'bar'), $this->handler_mod->module_output());
    }
    public function test_output_protected() {
        $this->handler_mod->out('foo', 'bar', true);
        $this->handler_mod->out('bar', 'foo', false);
        $this->assertEquals(array('foo'), $this->handler_mod->output_protected());
    }
    public function test_exists() {
        $this->handler_mod->out('foo', 'bar');
        $this->assertTrue($this->handler_mod->exists('foo'));
        $this->assertFalse($this->handler_mod->exists('blah'));
    }
    public function test_in() {
        $this->handler_mod->out('foo', 'bar');
        $this->assertTrue($this->handler_mod->in('foo', array('bar', 'baz')));
        $this->assertFalse($this->handler_mod->in('foo', array('baz', 'blah')));
    }

    /* tests for the Hm_Handler_Module class */
    public function test_process_nonce() {
        $this->parent->session->set('nonce_list', array('asdf'));
        $this->assertNull($this->handler_mod->process_nonce());
    }
    public function test_process_form() {
        list($success, $form) = $this->handler_mod->process_form(array('fld1', 'fld2'));
        $this->assertTrue($success);
        $this->assertEquals(array('fld1' => '0', 'fld2' => '1'), $form);
        list($success, $form) = $this->handler_mod->process_form(array('blah'));
        $this->assertFalse($success);
        $this->assertEquals(array(), $form);
    }

    /* tests for the Hm_Output_Module class */
    public function test_trans() {
        $this->assertEquals('inbox', $this->output_mod->trans('inbox'));
    }
    public function test_output_content() {
        /* TODO: fix */
        print_r($this->output_mod->output_content('JSON', array(), array('bar')));
    }
    public function test_html_safe() {
        $this->assertEquals('&lt;script&gt;', $this->output_mod->html_safe('<script>'));
    }

    /* tests for the Hm_Request_Handler class */
    public function test_process_request() {
        $res = $this->request_handler->process_request('test', $this->parent->request, $this->parent->session, $this->parent->config);
        $this->assertEquals(1, preg_match("/^\d\d:\d\d:\d\d/", $res['date']));
    }
    public function test_load_user_config_object() {
        $res = $this->request_handler->process_request('test', $this->parent->request, $this->parent->session, $this->parent->config);
        $this->request_handler->load_user_config_object();
    }
    public function test_default_language() {
    }
    public function test_run_modules() {
    }

    /* tests for Hm_Modules trait */
    public function test_queue_module_for_all_pages() {
    }
    public function test_load() {
    }
    public function test_set_source() {
    }
    public function test_add_to_all_pages() {
    }
    public function test_add() {
    }
    public function test_replace() {
    }
    public function test_swap_key() {
    }
    public function test_try_queued_modules() {
    }
    public function test_del() {
    }
    public function test_get_for_page() {
    }
    public function test_dump() {
    }

    /* TODO: test for functions */
}

?>

<?php
class ControllerExtensionModuleReshoppingcart extends Controller {
    private $error = array();

    public function install() {

    }

    public function uninstall() {

    }

    public function index() {
        $data = array();
        $this->load->language('extension/module/re_shopping_cart');
        $data = array_merge($data, $this->load->language('extension/module/re_shopping_cart'));

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
          
            $this->model_setting_setting->editSetting('module_re_shopping_cart', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        if(isset($this->request->post['module_re_shopping_cart_status'])) {
          $data['module_re_shopping_cart_status']  = $this->request->post['module_re_shopping_cart_status'];
        } else {
          $data['module_re_shopping_cart_status']  = $this->config->get('module_re_shopping_cart_status');
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/re_shopping_cart', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/re_shopping_cart', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/re_shopping_cart', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/re_shopping_cart')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}

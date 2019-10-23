<?php

namespace Hunter\eck\Controller;

use Zend\Diactoros\ServerRequest;
use Hunter\Core\Utility\StringConverter;

/**
 * Class page.
 *
 * @package Hunter\page\Controller
 */
class EckController {
  /**
   * eck_list.
   *
   * @return string
   *   Return eck_list string.
   */
  public function eck_list(ServerRequest $request) {
    $parms = $request->getQueryParams();
    if(!isset($parms['page'])){
      $parms['page'] = 1;
    }
    $eck_result = get_all_eck($parms);

    return view('/admin/eck-list.html', array('entitys' => $eck_result['list'], 'pager' => $eck_result['pager']));
  }

  /**
   * eck_add.
   *
   * @return string
   *   Return eck_add string.
   */
  public function eck_add(ServerRequest $request) {
    if ($parms = $request->getParsedBody()) {
      $info = array(
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => 1,
          'default' => '',
          'description' => 'page title.',
        ),
        'name' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => 1,
          'default' => '',
          'description' => 'page name.',
        ),
        'image' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => 1,
          'default' => '',
          'description' => 'news image.',
        ),
        'content' => array(
          'type' => 'text',
          'size' => 'big',
          'description' => "news content.",
        ),
        'uid' => array(
          'type' => 'varchar',
          'length' => 60,
          'not null' => TRUE,
          'default' => '',
          'description' => "page uid.",
        ),
        'status' => array(
          'type' => 'varchar',
          'length' => 9,
          'not null' => TRUE,
          'default' => '',
          'description' => 'page status.',
        ),
        'created' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the user was created.',
        ),
        'updated' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the user was updated.',
        )
      );
      $exist_table = db_schema()->tableExists($parms['machine_name']);
      if(!$exist_table) {
        //添加基础entity信息
        $fields = array();
        $fields_config = get_eck_fields_bymachine_name($parms['machine_name']);
        if(empty($fields_config)) {
          if(!empty($parms['base_field'])) {
            foreach (array_keys($parms['base_field']) as $key) {
              switch ($key) {
                case 'title':
                  $label = '标题';
                  $field_type = 'text';
                  $field_value_type = '';
                  break;
                case 'name':
                  $label = '名称';
                  $field_type = 'text';
                  $field_value_type = '';
                  break;
                case 'content':
                  $label = '内容';
                  $field_type = 'textarea';
                  $field_value_type = '';
                  break;
                case 'image':
                  $label = '图片';
                  $field_type = 'image';
                  $field_value_type = 'single';
                  break;
                case 'uid':
                  $label = '用户ID';
                  $field_type = 'system';
                  $field_value_type = '';
                  break;
                case 'created':
                  $label = '创建日期';
                  $field_type = 'system';
                  $field_value_type = '';
                  break;
                case 'updated':
                  $label = '更新日期';
                  $field_type = 'system';
                  $field_value_type = '';
                  break;
                default:
                  break;
              }
              $fields[] = array(
                'label' => $label,
                'field_machine_name' => $key,
                'field_type' => $field_type,
                'field_value_type' => $field_value_type
              );
            }
          }
        }else {
          $fields = $fields_config + $fields_config;
        }

        $etid = db_insert('entity_type')
          ->fields(array(
            'name' => clean($parms['name']),
            'machine_name' => clean($parms['machine_name']),
            'base_field' => !empty($parms['base_field']) ? implode('|', array_keys($parms['base_field'])) : '',
            'fields_config' => json_encode($fields)
          ))
          ->execute();

        //创建表
        $fields = array(
          substr($parms['machine_name'], 0, 1 ).'id' => array(
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'ID of page.',
          ),
        );

        if(!empty($parms['base_field'])) {
          foreach (array_keys($parms['base_field']) as $key) {
            $fields[$key] = $info[$key];
          }
          $schema = array(
            'description' => 'user with role.',
            'fields' => $fields,
            'primary key' => array(substr($parms['machine_name'], 0, 1 ).'id'),
          );
        }else {
          $schema = array(
            'description' => 'user with role.',
            'fields' => $fields,
            'primary key' => array(substr($parms['machine_name'], 0, 1 ).'id'),
          );
        }

        db_schema()->createTable($parms['machine_name'], $schema);
        hunter_set_message($parms['machine_name']. ' Entity类型创建成功!');
      }else {
        hunter_set_message($parms['machine_name']. ' 表已经存在!', 'error');
      }

      return redirect('/admin/eck');
    }

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => '名称',
      '#id' => 'name',
    );
    $form['machine_name'] = array(
      '#type' => 'textfield',
      '#title' => '机器名',
      '#id' => 'machine_name',
      '#hidden' => true,
      '#prefix' => '<div id="machine_name_wapper" style="display:none;">',
      '#suffix' => '</div>',
    );
    $form['base_field'] = array(
      '#type' => 'checkboxes',
      '#title' => '基础字段',
      '#default_value' => '',
      '#options' => array('title' => '标题', 'name' => '名称', 'content' => '内容', 'image' => '图片', 'uid' => '用户ID', 'created' => '创建日期', 'updated' => '更新日期')
    );
    $form['save'] = array(
     '#type' => 'submit',
     '#value' => t('创建'),
    );

    return view('/admin/eck-add.html', array('form' => $form));
  }

  /**
   * eck_edit.
   *
   * @return string
   *   Return eck_edit string.
   */
  public function eck_edit($etid) {
      $eck = get_eck_byid($etid);

      $form['name'] = array(
        '#type' => 'textfield',
        '#title' => '标题',
        '#default_value' => $eck->name,
        '#maxlength' => 255,
      );
      $form['machine_name'] = array(
        '#type' => 'hidden',
        '#value' => $eck->machine_name,
      );
      $form['base_field'] = array(
        '#type' => 'checkboxes',
        '#title' => '基础字段',
        '#disable_selected' => true,
        '#default_value' => $eck->base_field != null ? explode('|', $eck->base_field) : '',
        '#options' => array('title' => '标题', 'name' => '名称', 'content' => '内容', 'image' => '图片', 'uid' => '用户ID', 'created' => '创建日期', 'updated' => '更新日期')
      );
      $form['etid'] = array(
        '#type' => 'hidden',
        '#value' => $etid,
      );

      $form['save'] = array(
       '#type' => 'submit',
       '#value' => t('提交'),
      );

      $form['redirect'] = '/admin/eck/update';

      return view('/admin/eck-edit.html', array('form' => $form, 'eck' => $eck, 'pid' => $etid));
  }

  /**
   * eck_update.
   *
   * @return string
   *   Return eck_update string.
   */
  public function eck_update(ServerRequest $request) {
    if ($parms = $request->getParsedBody()) {
      $info = array(
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => 1,
          'default' => '',
          'description' => 'page title.',
        ),
        'name' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => 1,
          'default' => '',
          'description' => 'page name.',
        ),
        'image' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => 1,
          'default' => '',
          'description' => 'news image.',
        ),
        'content' => array(
          'type' => 'text',
          'size' => 'big',
          'description' => "news content.",
        ),
        'uid' => array(
          'type' => 'varchar',
          'length' => 60,
          'not null' => TRUE,
          'default' => '',
          'description' => "page uid.",
        ),
        'status' => array(
          'type' => 'varchar',
          'length' => 9,
          'not null' => TRUE,
          'default' => '',
          'description' => 'page status.',
        ),
        'created' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the user was created.',
        ),
        'updated' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the user was updated.',
        )
      );
      //添加基础entity信息
      $fields = array();
      $fields_config = get_eck_fields_bymachine_name($parms['machine_name']);
      if(!empty($parms['base_field'])) {
        foreach (array_keys($parms['base_field']) as $key) {
          switch ($key) {
            case 'title':
              $label = '标题';
              $field_type = 'text';
              $field_value_type = '';
              break;
            case 'name':
              $label = '名称';
              $field_type = 'text';
              $field_value_type = '';
              break;
            case 'content':
              $label = '内容';
              $field_type = 'textarea';
              $field_value_type = '';
              break;
            case 'image':
              $label = '图片';
              $field_type = 'image';
              $field_value_type = 'single';
              break;
            case 'uid':
              $label = '用户ID';
              $field_type = 'system';
              $field_value_type = '';
              break;
            case 'created':
              $label = '创建日期';
              $field_type = 'system';
              $field_value_type = '';
              break;
            case 'updated':
              $label = '更新日期';
              $field_type = 'system';
              $field_value_type = '';
              break;
            default:
              break;
          }
          $fields[] = array(
            'label' => $label,
            'field_machine_name' => $key,
            'field_type' => $field_type,
            'field_value_type' => $field_value_type
          );
        }
        $fields = array_merge($fields_config, $fields);
      }
      $base_field = get_eck_base_fields_bymachine_name($parms['machine_name']);

      $etid = db_update('entity_type')
        ->fields(array(
          'name' => clean($parms['name']),
          'base_field' => !empty($parms['base_field']) ? implode('|', array_merge($base_field, array_keys($parms['base_field']))) : '',
          'fields_config' => json_encode($fields)
        ))
        ->condition('machine_name', $parms['machine_name'])
        ->execute();

      //创建表
      if(!empty($parms['base_field'])) {
        foreach (array_keys($parms['base_field']) as $key) {
          db_schema()->addField($parms['machine_name'], $key, $info[$key]);
        }
      }

      hunter_set_message($parms['machine_name']. ' Entity 类型修改成功!');

      return redirect('/admin/eck');
    }
  }

  /**
   * eck_del.
   *
   * @return string
   *   Return eck_del string.
   */
  public function eck_del($etid) {
    $machine_name = get_eck_machine_name_byid($etid);
    $content = get_eck_content($machine_name);
    if(!empty($content)) {
      hunter_set_message('当前有'.count($content).'条'.$machine_name.'内容，在删除所有'.$machine_name.'内容之前, 你不能删除该Entity!', 'error');
    }else {
      $result = db_delete('entity_type')
              ->condition('etid', $etid)
              ->execute();

      if ($result) {
        db_schema()->dropTable($machine_name);
        hunter_set_message($machine_name. ' Entity 删除成功!');
      }
    }

    return redirect('/admin/eck');
  }

  /**
   * eck_fields.
   *
   * @return string
   *   Return eck_fields string.
   */
  public function eck_fields($machine_name) {
    $fields = get_eck_fields_bymachine_name($machine_name);
    return view('/admin/eck-fields.html', array('fields' => $fields, 'machine_name' => $machine_name));
  }

  /**
   * eck_field_add.
   *
   * @return string
   *   Return eck_field_add string.
   */
  public function eck_field_add(ServerRequest $request, $machine_name) {
    if ($parms = $request->getParsedBody()) {
      if(!isset($parms['field_machine_name'])) {
        $parms['field_machine_name'] = machine_name($parms['label']);
      }
      $exist_field = db_schema()->fieldExists($machine_name, $parms['field_machine_name']);
      if(!$exist_field) {
        //修改entity字段配置信息
        $fields = array();
        $fields[] = array(
          'label' => $parms['label'],
          'field_machine_name' => $parms['field_machine_name'],
          'field_type' => $parms['field_type'],
          'field_default_value' => $parms['field_default_value'],
          'field_value_type' => isset($parms['field_value_type']) ? $parms['field_value_type'] : '',
          'field_category_partent' => isset($parms['field_category_partent']) ? $parms['field_category_partent'] : '',
          'field_options' => isset($parms['field_options']) ? $parms['field_options'] : ''
        );
        $fields_config = get_eck_fields_bymachine_name($machine_name);
        if(!empty($fields_config)) {
          $fields = array_merge($fields_config, $fields);
        }

        db_update('entity_type')
          ->fields(array(
            'fields_config' => json_encode($fields),
          ))
          ->condition('machine_name', $machine_name)
          ->execute();

        //添加字段
        if(!empty($parms['field_type'])) {
          switch ($parms['field_type']) {
            case 'text': case 'radios': case 'checkboxes': case 'select': case 'category':
              $spec = array(
                'type' => 'varchar',
                'length' => 255,
                'not null' => FALSE,
                'default' => $parms['field_default_value'],
                'description' => $machine_name.' '.$parms['field_machine_name'],
              );
              break;
            case 'textarea':
              $spec = array(
                'type' => 'text',
                'size' => 'big',
                'description' => $machine_name.' '.$parms['field_machine_name'],
              );
              break;
            case 'image':
              if(isset($parms['field_value_type']) && $parms['field_value_type'] == 'single') {
                $spec = array(
                  'type' => 'varchar',
                  'length' => 255,
                  'not null' => 1,
                  'default' => $parms['field_default_value'],
                  'description' => $machine_name.' '.$parms['field_machine_name'],
                );
              }else {
                $spec = array(
                  'type' => 'blob',
                  'not null' => 1,
                  'size' => 'big',
                  'description' => $machine_name.' '.$parms['field_machine_name'],
                );
              }
              break;
            case 'int': case 'timestamp':
              $spec = array(
                'type' => 'int',
                'not null' => 1,
                'default' => $parms['field_default_value'],
                'description' => $machine_name.' '.$parms['field_machine_name'],
              );
              break;

            default:
              $spec = array(
                'type' => 'varchar',
                'length' => 255,
                'not null' => 1,
                'default' => $parms['field_default_value'],
                'description' => $machine_name.' '.$parms['field_machine_name'],
              );
              break;
          }
          db_schema()->addField($machine_name, $parms['field_machine_name'], $spec);
        }

        hunter_set_message($parms['field_machine_name']. ' 字段添加成功!');
      }else {
        hunter_set_message($parms['field_machine_name']. ' 字段已经存在!', 'error');
      }

      return redirect('/admin/eck/'.$machine_name.'/fields');
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => '标签名',
      '#id' => 'name',
      '#attributes' => array('v-model' => 'label', 'v-on:keyup' => 'getMachineName'),
    );
    $form['field_machine_name'] = array(
      '#type' => 'textfield',
      '#title' => '机器名',
      '#id' => 'machine_name',
      '#hidden' => true,
      '#attributes' => array('v-model' => 'field_machine_name'),
      '#prefix' => '<div id="machine_name_wapper" v-if="field_machine_name">',
      '#suffix' => '</div>',
    );
    $form['field_type'] = array(
      '#type' => 'select',
      '#title' => '字段类型',
      '#default_value' => '',
      '#options' => array('text' => '文本框', 'textarea' => '文本域', 'textarea_with_editor' => '文本域 (带编辑器)', 'image' => '图片', 'file' => '文件', 'radios' => '单选框', 'checkboxes' => '多选框', 'select' => '下拉选择', 'int' => '整数', 'timestamp' => '时间戳', 'category' => '分类'),
      '#attributes' => array('v-model' => 'field_type'),
    );
    $form['field_options'] = array(
      '#type' => 'textarea',
      '#title' => '字段选项',
      '#description' => '格式为：key|label, 一行一个',
      '#attributes' => array('v-model' => 'field_options'),
      '#prefix' => '<div id="machine_name_wapper" v-if="field_type == \'radios\' || field_type == \'checkboxes\' || field_type == \'select\'">',
      '#suffix' => '</div>',
    );
    $form['field_value_type'] = array(
      '#type' => 'radios',
      '#title' => '值类型',
      '#id' => 'field_value_type',
      '#default_value' => 'single',
      '#options' => array('single' => '单值', 'multi' => '多值'),
      '#attributes' => array('v-model' => 'field_value_type'),
      '#prefix' => '<div id="value_type_wapper" v-if="field_type == \'image\' || field_type == \'select\'">',
      '#suffix' => '</div>',
    );
    $category_options = array();
    $category_options = get_tree_options(0, 1);
    $form['field_category_partent'] = array(
      '#type' => 'markup',
      '#title' => '选择分类',
      '#markup' => '<select name="field_category_partent" v-model="field_category_partent">'.$category_options.'</select>',
      '#prefix' => '<div id="category_partent_wapper" v-if="field_type == \'category\'">',
      '#suffix' => '</div>',
    );
    $form['field_default_value'] = array(
      '#type' => 'textfield',
      '#title' => '默认值',
      '#id' => 'field_default_value',
      '#attributes' => array('v-model' => 'field_default_value'),
    );
    $form['save'] = array(
     '#type' => 'submit',
     '#value' => t('添加'),
    );

    return view('/admin/eck-field-add.html', array('form' => $form, 'machine_name' => $machine_name));
  }

  /**
   * eck_field_edit.
   *
   * @return string
   *   Return eck_field_edit string.
   */
  public function eck_field_edit(ServerRequest $request, $machine_name, $field_machine_name) {
    if ($parms = $request->getParsedBody()) {
      //修改entity字段配置信息
      $fields_config = get_eck_fields_bymachine_name($machine_name);
      $spec = array();
      foreach ($fields_config as $key => $value) {
        if($value->field_machine_name == $field_machine_name) {
          if($value->field_type != $parms['field_type']) {
            switch ($parms['field_type']) {
              case 'text': case 'radios': case 'checkboxes': case 'select': case 'category':
                $spec = array(
                  'type' => 'varchar',
                  'length' => 255,
                  'not null' => FALSE,
                  'default' => $parms['field_default_value'],
                  'description' => $machine_name.' '.$parms['field_machine_name'],
                );
                break;
              case 'textarea': case 'textarea_with_editor':
                $spec = array(
                  'type' => 'text',
                  'size' => 'big',
                  'description' => $machine_name.' '.$parms['field_machine_name'],
                );
                break;
              case 'image':
                if(isset($parms['field_value_type']) && $parms['field_value_type'] == 'single') {
                  $spec = array(
                    'type' => 'varchar',
                    'length' => 255,
                    'not null' => FALSE,
                    'default' => $parms['field_default_value'],
                    'description' => $machine_name.' '.$parms['field_machine_name'],
                  );
                }else {
                  $spec = array(
                    'type' => 'blob',
                    'not null' => FALSE,
                    'size' => 'big',
                    'description' => $machine_name.' '.$parms['field_machine_name'],
                  );
                }
                break;
              case 'int': case 'timestamp':
                $spec = array(
                  'type' => 'int',
                  'not null' => FALSE,
                  'default' => $parms['field_default_value'],
                  'description' => $machine_name.' '.$parms['field_machine_name'],
                );
                break;

              default:
                $spec = array(
                  'type' => 'varchar',
                  'length' => 255,
                  'not null' => FALSE,
                  'default' => $parms['field_default_value'],
                  'description' => $machine_name.' '.$parms['field_machine_name'],
                );
                break;
            }
          }
          $fields_config[$key]->label = $parms['label'];
          $fields_config[$key]->field_type = $parms['field_type'];
          $fields_config[$key]->field_default_value = $parms['field_default_value'];
          $fields_config[$key]->field_value_type = isset($parms['field_value_type']) ? $parms['field_value_type'] : '';
          $fields_config[$key]->field_category_partent = isset($parms['field_category_partent']) ? $parms['field_category_partent'] : '';
          $fields_config[$key]->field_options = isset($parms['field_options']) ? $parms['field_options'] : '';
        }
      }

      db_update('entity_type')
        ->fields(array(
          'fields_config' => json_encode($fields_config),
        ))
        ->condition('machine_name', $machine_name)
        ->execute();

      //修改字段
      if(!empty($spec)) {
        db_schema()->changeField($machine_name, $parms['field_machine_name'], $parms['field_machine_name'], $spec);
      }

      hunter_set_message($parms['field_machine_name']. ' 字段修改成功!');

      return redirect('/admin/eck/'.$machine_name.'/fields');
    }

    $field = array();
    $fields_config = get_eck_fields_bymachine_name($machine_name);
    foreach ($fields_config as $key => $value) {
      if($value->field_machine_name == $field_machine_name) {
        $field = $fields_config[$key];
      }
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => '标签名',
      '#id' => 'name',
      '#attributes' => array('v-model' => 'label', 'v-on:keyup' => 'getMachineName'),
    );
    $form['field_machine_name'] = array(
      '#type' => 'textfield',
      '#title' => '机器名',
      '#id' => 'machine_name',
      '#hidden' => true,
      '#attributes' => array('v-model' => 'field_machine_name'),
      '#prefix' => '<div id="machine_name_wapper" v-if="field_machine_name">',
      '#suffix' => '</div>',
    );
    $form['field_type'] = array(
      '#type' => 'select',
      '#title' => '字段类型',
      '#default_value' => '',
      '#options' => array('text' => '文本框', 'textarea' => '文本域', 'textarea_with_editor' => '文本域 (带编辑器)', 'image' => '图片', 'file' => '文件', 'radios' => '单选框', 'checkboxes' => '多选框', 'select' => '下拉选择', 'int' => '整数', 'timestamp' => '时间戳', 'category' => '分类'),
      '#attributes' => array('v-model' => 'field_type'),
    );
    $form['field_options'] = array(
      '#type' => 'textarea',
      '#title' => '字段选项',
      '#description' => '格式为：key|label, 一行一个',
      '#attributes' => array('v-model' => 'field_options'),
      '#prefix' => '<div id="machine_name_wapper" v-if="field_type == \'radios\' || field_type == \'checkboxes\' || field_type == \'select\'">',
      '#suffix' => '</div>',
    );
    $form['field_value_type'] = array(
      '#type' => 'radios',
      '#title' => '值类型',
      '#id' => 'field_value_type',
      '#default_value' => 'single',
      '#options' => array('single' => '单值', 'multi' => '多值'),
      '#attributes' => array('v-model' => 'field_value_type'),
      '#prefix' => '<div id="value_type_wapper" v-if="field_type == \'image\' || field_type == \'select\'">',
      '#suffix' => '</div>',
    );
    $category_options = array();
    $category_options = get_tree_options(0, 1);
    $form['field_category_partent'] = array(
      '#type' => 'markup',
      '#title' => '选择分类',
      '#markup' => '<select name="field_category_partent" v-model="field_category_partent">'.$category_options.'</select>',
      '#prefix' => '<div id="category_partent_wapper" v-if="field_type == \'category\'">',
      '#suffix' => '</div>',
    );
    $form['field_default_value'] = array(
      '#type' => 'textfield',
      '#title' => '默认值',
      '#id' => 'field_default_value',
      '#attributes' => array('v-model' => 'field_default_value'),
    );
    $form['save'] = array(
     '#type' => 'submit',
     '#value' => t('修改'),
    );

    $phptojs = phptojs()->put(['field' => (array) $field]);

    return view('/admin/eck-field-edit.html', array('form' => $form, 'machine_name' => $machine_name, 'field' => $field, 'phptojs' => $phptojs));
  }

  /**
   * eck_field_del.
   *
   * @return string
   *   Return eck_field_del string.
   */
  public function eck_field_del(ServerRequest $request, $machine_name, $field_machine_name) {
    $fields = get_eck_fields_bymachine_name($machine_name);

    foreach ($fields as $key => $field) {
      if($field->field_machine_name == $field_machine_name) {
        unset($fields[$key]);
      }
    }
    $fields = array_values($fields);

    db_update('entity_type')
      ->fields(array(
        'fields_config' => json_encode($fields),
      ))
      ->condition('machine_name', $machine_name)
      ->execute();

    db_schema()->dropField($machine_name, $field_machine_name);
    hunter_set_message($field_machine_name. ' 字段删除成功!');

    return redirect('/admin/eck/'.$machine_name.'/fields');
  }

  /**
   * eck_content.
   *
   * @return string
   *   Return eck_content string.
   */
  public function eck_content($machine_name) {
    $contents = get_eck_content($machine_name);
    $fields = get_eck_fields_bymachine_name($machine_name);
    return view('/admin/eck-content.html', array('contents' => $contents, 'fields' => $fields, 'machine_name' => $machine_name));
  }

  /**
   * eck_content_add.
   *
   * @return string
   *   Return eck_content_add string.
   */
  public function eck_content_add(ServerRequest $request, $machine_name) {
    $fields = get_eck_fields_bymachine_name($machine_name);

    if ($parms = $request->getParsedBody()) {
      if(!empty($fields)) {
        $insert = array();
        foreach ($fields as $f) {
          if($f->field_type !== 'system') {
            switch ($f->field_type) {
              case 'image':
                if($f->field_value_type == 'multi') {
                  $photos = array();
                  if(isset($parms['up_photos']) || !empty($parms['up_photos'])){
                    foreach ($parms['up_photos'] as $img) {
                      $photos[] = array('image' => $img['img'], 'description' => '');
                    }
                  }
                  $insert[$f->field_machine_name] = json_encode($photos);
                }else {
                  $insert[$f->field_machine_name] = $parms[$f->field_machine_name];
                }
                break;
              case 'checkboxes':
                if(!empty($parms[$f->field_machine_name])) {
                  $insert[$f->field_machine_name] = isset($parms[$f->field_machine_name]) ? implode("|", array_keys($parms[$f->field_machine_name])).'|' : NULL;
                }else {
                  $insert[$f->field_machine_name] = NULL;
                }
                break;
              case 'select':
                if($f->field_value_type == 'multi') {
                  $insert[$f->field_machine_name] = isset($parms[$f->field_machine_name]) ? implode("|", $parms[$f->field_machine_name]).'|' : NULL;
                }else {
                  $insert[$f->field_machine_name] = $parms[$f->field_machine_name];
                }
                break;

              default:
                $insert[$f->field_machine_name] = $parms[$f->field_machine_name];
                break;
            }
          }else {
            switch ($f->field_machine_name) {
              case 'uid':
                $user = session()->get('admin');
                $insert['uid'] = $user->uid;
                break;
              case 'created': case 'updated':
                $insert[$f->field_machine_name] = time();
                break;
              default:
                break;
            }
          }
        }
        $pid = db_insert($machine_name)
          ->fields($insert)
          ->execute();

        hunter_form_submit($parms, $machine_name, $pid);
      }
       return redirect('/admin/eck/'.$machine_name.'/content');
    }

    $page_bottom = '';
    $img_upload_js = false;
    $ueditor_js = false;

    if(!empty($fields)) {
      foreach ($fields as $f) {
        switch ($f->field_type) {
          case 'text':
            $form[$f->field_machine_name] = array(
              '#type' => 'textfield',
              '#title' => $f->label,
              '#maxlength' => 255,
            );
            break;
          case 'image':
            $form[$f->field_machine_name] = array(
              '#type' => 'image',
              '#title' => $f->label,
              '#skin' => 'simple',
              '#attributes' => array('id' => 'images'),
            );
            if($f->field_value_type == 'multi') {
              $form[$f->field_machine_name]['#multiple'] = TRUE;
            }
            $form['image_preview'] = array(
              '#type' => 'markup',
              '#title' => '预览',
              '#hidden' => TRUE,
              '#markup' => '<img src="" id="image-preview" width="200">',
            );
            if(!$img_upload_js) {
              $page_bottom .= "<script src=\"/theme/seven/assets/js/liteuploader.js\"></script>
              <script src=\"/theme/seven/assets/js/simple-image-upload.js\"></script>";
              $img_upload_js = true;
            }
            break;
          case 'textarea':
            $form[$f->field_machine_name] = array(
              '#type' => 'textarea',
              '#title' => $f->label,
            );
            break;
          case 'textarea_with_editor':
            $form[$f->field_machine_name] = array(
              '#type' => 'textarea',
              '#title' => $f->label,
              '#attributes' => array('class' => 'ueditor', 'id' => $f->field_machine_name),
            );
            if(!$ueditor_js) {
              $page_bottom .= "
                  <script src=\"/theme/seven/assets/ueditor/ueditor.config.js\"></script>
                  <script src=\"/theme/seven/assets/ueditor/ueditor.all.js\"></script>";
              $ueditor_js = true;
            }
            $page_bottom .= '
            <script>
              $(function () {
                var '.$f->field_machine_name.'Ue = UE.getEditor(\''.$f->field_machine_name.'\', {
                  initialFrameWidth: \'100%\',
                  initialFrameHeight: 300
                });
              })
            </script>';
          break;
          case 'category':
            $cat_options = array();
            if(!isset($f->field_category_partent)) {
              $f->field_category_partent = 0;
            }
            $cats = get_subcats_byid($f->field_category_partent, true, true);
            foreach ($cats as $key => $item) {
              $cat_options[$key] = $item->name;
            }
            $form[$f->field_machine_name] = array(
              '#type' => 'select',
              '#title' => $f->label,
              '#default_value' => $f->field_default_value,
              '#options' => $cat_options,
              '#attributes' => array('id' => $f->field_machine_name),
            );
            break;
          case 'radios': case 'checkboxes': case 'select':
            if(isset($f->field_options) && !is_array($f->field_options)) {
              $options_array = array();
              $options = explode("\n", $f->field_options);
              foreach ($options as $op) {
                list($key, $val) = explode("|", $op);
                 $options_array[$key] = $val;
              }
              $f->field_options = $options_array;
            }

            $form[$f->field_machine_name] = array(
              '#type' => $f->field_type,
              '#title' => $f->label,
              '#default_value' => $f->field_default_value,
              '#options' => $f->field_options,
            );
            if($f->field_type == 'select') {
              $form[$f->field_machine_name]['#type'] = 'select';
              if($f->field_value_type == 'multi'){
                $form[$f->field_machine_name]['#multiple'] = true;
              }
            }
            break;
          default:
            // code...
            break;
        }
      }
    }

    $form['save'] = array(
     '#type' => 'submit',
     '#value' => t('提交')
    );

    return view('/admin/eck-content-add.html', array('form' => $form, 'fields' => $fields, 'machine_name' => $machine_name, 'page_bottom' => $page_bottom));
  }

  /**
   * eck_content_edit.
   *
   * @return string
   *   Return eck_content_edit string.
   */
  public function eck_content_edit(ServerRequest $request, $machine_name, $entity_content_id) {
    $fields = get_eck_fields_bymachine_name($machine_name);
    $content = get_eck_content_byid($machine_name, $entity_content_id);

    if ($parms = $request->getParsedBody()) {
      if(!empty($fields)) {
        $insert = array();
        foreach ($fields as $f) {
          if($f->field_type !== 'system') {
            switch ($f->field_type) {
              case 'image':
                if($f->field_value_type == 'multi') {
                  $photos = array();
                  if(isset($parms['up_photos']) || !empty($parms['up_photos'])){
                    foreach ($parms['up_photos'] as $img) {
                      $photos[] = array('image' => $img['img'], 'description' => '');
                    }
                  }
                  $insert[$f->field_machine_name] = json_encode($photos);
                }else {
                  $insert[$f->field_machine_name] = $parms[$f->field_machine_name];
                }
                break;
              case 'checkboxes':
                if(!empty($parms[$f->field_machine_name])) {
                  $insert[$f->field_machine_name] = isset($parms[$f->field_machine_name]) ? implode("|", array_keys($parms[$f->field_machine_name])).'|' : NULL;
                }else {
                  $insert[$f->field_machine_name] = NULL;
                }
                break;
              case 'select':
                if($f->field_value_type == 'multi') {
                  $insert[$f->field_machine_name] = isset($parms[$f->field_machine_name]) ? implode("|", $parms[$f->field_machine_name]).'|' : NULL;
                }else {
                  $insert[$f->field_machine_name] = $parms[$f->field_machine_name];
                }
                break;

              default:
                $insert[$f->field_machine_name] = $parms[$f->field_machine_name];
                break;
            }
          }else {
            switch ($f->field_machine_name) {
              case 'uid':
                $user = session()->get('admin');
                $insert['uid'] = $user->uid;
                break;
              case 'created': case 'updated':
                $insert[$f->field_machine_name] = time();
                break;
              default:
                break;
            }
          }
        }

        $pid = db_update($machine_name)
          ->fields($insert)
          ->condition(substr($machine_name, 0, 1 ).'id', $entity_content_id)
          ->execute();

        hunter_form_submit($parms, $machine_name, $pid);
      }
       return redirect('/admin/eck/'.$machine_name.'/content');
    }

    $page_bottom = '';
    $img_upload_js = false;
    $ueditor_js = false;

    if(!empty($fields)) {
      foreach ($fields as $f) {
        switch ($f->field_type) {
          case 'text':
            $form[$f->field_machine_name] = array(
              '#type' => 'textfield',
              '#title' => $f->label,
              '#default_value' => $content->{$f->field_machine_name},
              '#maxlength' => 255,
            );
            break;
          case 'image':
            $form[$f->field_machine_name] = array(
              '#type' => 'image',
              '#title' => $f->label,
              '#skin' => 'simple',
              '#default_value' => $content->{$f->field_machine_name},
              '#attributes' => array('id' => 'images'),
            );
            if($f->field_value_type == 'multi') {
              $form[$f->field_machine_name]['#multiple'] = TRUE;
            }
            $form['image_preview'] = array(
              '#type' => 'markup',
              '#title' => '预览',
              '#hidden' => TRUE,
              '#default_value' => $content->{$f->field_machine_name},
              '#markup' => '<img src="" id="image-preview" width="200">',
            );
            if(!$img_upload_js) {
              $page_bottom .= "<script src=\"/theme/seven/assets/js/liteuploader.js\"></script>
              <script src=\"/theme/seven/assets/js/simple-image-upload.js\"></script>";
              $img_upload_js = true;
            }
            break;
          case 'textarea':
            $form[$f->field_machine_name] = array(
              '#type' => 'textarea',
              '#title' => $f->label,
              '#default_value' => $content->{$f->field_machine_name},
            );
            break;
          case 'textarea_with_editor':
            $form[$f->field_machine_name] = array(
              '#type' => 'textarea',
              '#title' => $f->label,
              '#default_value' => $content->{$f->field_machine_name},
              '#attributes' => array('class' => 'ueditor', 'id' => $f->field_machine_name),
            );
            if(!$ueditor_js) {
              $page_bottom .= "
                  <script src=\"/theme/seven/assets/ueditor/ueditor.config.js\"></script>
                  <script src=\"/theme/seven/assets/ueditor/ueditor.all.js\"></script>";
              $ueditor_js = true;
            }
            $page_bottom .= '
            <script>
              $(function () {
                var '.$f->field_machine_name.'Ue = UE.getEditor(\''.$f->field_machine_name.'\', {
                  initialFrameWidth: \'100%\',
                  initialFrameHeight: 300
                });
              })
            </script>';
          break;
          case 'category':
            $cat_options = array();
            if(!isset($f->field_category_partent)) {
              $f->field_category_partent = 0;
            }
            $cats = get_subcats_byid($f->field_category_partent, true, true);
            foreach ($cats as $key => $item) {
              $cat_options[$key] = $item->name;
            }
            $form[$f->field_machine_name] = array(
              '#type' => 'select',
              '#title' => $f->label,
              '#default_value' => $content->{$f->field_machine_name},
              '#options' => $cat_options,
              '#attributes' => array('id' => $f->field_machine_name),
            );
            break;
          case 'radios': case 'checkboxes': case 'select':
            if(isset($f->field_options) && !is_array($f->field_options)) {
              $options_array = array();
              $options = explode("\n", $f->field_options);
              foreach ($options as $op) {
                list($key, $val) = explode("|", $op);
                 $options_array[$key] = $val;
              }
              $f->field_options = $options_array;
            }

            $form[$f->field_machine_name] = array(
              '#type' => $f->field_type,
              '#title' => $f->label,
              '#default_value' => $content->{$f->field_machine_name},
              '#options' => $f->field_options,
            );
            if($f->field_type == 'select') {
              $form[$f->field_machine_name]['#type'] = 'select';
              if($f->field_value_type == 'multi'){
                $form[$f->field_machine_name]['#default_value'] = explode("|", $content->{$f->field_machine_name});
                $form[$f->field_machine_name]['#multiple'] = true;
              }
            }
            break;
          default:
            break;
        }
      }
    }

    $form['entity_content_id'] = array(
      '#type' => 'hidden',
      '#value' => $entity_content_id,
    );

    $form['save'] = array(
     '#type' => 'submit',
     '#value' => t('提交')
    );

    return view('/admin/eck-content-edit.html', array('form' => $form, 'fields' => $fields, 'machine_name' => $machine_name, 'entity_content_id' => $entity_content_id, 'content' => $content, 'page_bottom' => $page_bottom));
  }

  /**
   * eck_content_del.
   *
   * @return string
   *   Return eck_content_del string.
   */
  public function eck_content_del(ServerRequest $request, $machine_name, $entity_content_id) {
    $result = db_delete($machine_name)
            ->condition(substr($machine_name, 0, 1 ).'id', $entity_content_id)
            ->execute();

    if ($result) {
      hunter_set_message('删除成功!');
    }else {
      hunter_set_message('删除失败!', 'error');
    }

    return redirect('/admin/eck/'.$machine_name.'/content');
  }

}

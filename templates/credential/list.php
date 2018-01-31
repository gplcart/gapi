<?php
/**
 * @package Backup
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<?php if($this->access('module_gapi_credential_add')) { ?>
  <div class="btn-toolbar actions">
    <?php foreach($handlers as $id => $handler) { ?>
      <a class="btn btn-default" href="<?php echo $this->url("admin/module/settings/gapi/credential/add/$id"); ?>">
          <?php echo $this->text('Add @name', array('@name' => $handler['name'])); ?>
      </a>
    <?php } ?>
  </div>
<?php } ?>
<?php if (!empty($credentials)) { ?>
<form method="post">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
<?php if ($this->access('module_gapi_credential_delete')) { ?>
<div class="form-inline actions">
  <div class="input-group">
    <select name="action[name]" class="form-control" onchange="Gplcart.action(this);">
      <option value=""><?php echo $this->text('With selected'); ?></option>
      <option value="delete" data-confirm="<?php echo $this->text('Are you sure? It cannot be undone!'); ?>">
        <?php echo $this->text('Delete'); ?>
      </option>
    </select>
    <span class="input-group-btn hidden-js">
      <button class="btn btn-default" name="action[submit]" value="1"><?php echo $this->text('OK'); ?></button>
    </span>
  </div>
</div>
<?php } ?>
<div class="table-responsive">
  <table class="table backups">
    <thead>
      <tr>
        <th>
          <input type="checkbox" onchange="Gplcart.selectAll(this);">
        </th>
        <th>
          <a href="<?php echo $sort_credential_id; ?>"><?php echo $this->text('ID'); ?> <i class="fa fa-sort"></i></a>
        </th>
        <th>
          <a href="<?php echo $sort_name; ?>"><?php echo $this->text('Name'); ?> <i class="fa fa-sort"></i></a>
        </th>
        <th>
          <a href="<?php echo $sort_type; ?>"><?php echo $this->text('Type'); ?> <i class="fa fa-sort"></i></a>
        </th>
        <th>
          <a href="<?php echo $sort_created; ?>"><?php echo $this->text('Created'); ?> <i class="fa fa-sort"></i></a>
        </th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($credentials as $credential) { ?>
      <tr>
        <td class="middle">
          <input type="checkbox" class="select-all" name="action[items][]" value="<?php echo $credential['credential_id']; ?>">
        </td>
        <td class="middle"><?php echo $this->e($credential['credential_id']); ?></td>
        <td class="middle"><?php echo $this->e($credential['name']); ?></td>
        <td class="middle">
            <?php if(isset($handlers[$credential['type']]['name'])) {  ?>
            <?php echo $this->e($handlers[$credential['type']]['name']); ?></td>
            <?php } else { ?>
                <?php echo $this->text('Unknown'); ?>
            <?php } ?>
        <td class="middle">
          <?php echo $this->date($credential['created']); ?>
        </td>
        <td class="middle">
          <ul class="list-inline">
            <?php if ($this->access('module_gapi_credential_edit')) { ?>
            <a href="<?php echo $this->url("admin/module/settings/gapi/credential/edit/{$credential['credential_id']}"); ?>">
              <?php echo $this->lower($this->text('Edit')); ?>
            </a>
            <?php } ?>
          </ul>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<?php if (!empty($_pager)) { ?>
<?php echo $_pager; ?>
<?php } ?>
</form>
<?php } else { ?>
<div class="row">
  <div class="col-md-12">
    <?php echo $this->text('There are no items yet'); ?>
  </div>
</div>
<?php } ?>


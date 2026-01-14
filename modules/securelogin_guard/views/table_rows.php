<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($whitelist)): ?>
    <tr>
        <td colspan="<?php echo $is_admin ? '6' : '5'; ?>" class="text-center text-muted">
            <?php echo _l('no_ip_addresses'); ?>
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($whitelist as $item): ?>
        <tr>
            <td>
                <code><?php echo htmlspecialchars($item->ip_address); ?></code>
            </td>
            <?php if ($is_admin): ?>
            <td>
                <?php if ($item->staff_id): ?>
                    <strong><?php echo _l('for_staff'); ?>:</strong> 
                    <span class="label label-primary"><?php echo get_staff_full_name($item->staff_id); ?></span>
                <?php else: ?>
                    <span class="label label-info"><?php echo _l('all_staff'); ?></span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
            <td>
                <?php echo htmlspecialchars($item->description ? $item->description : '-'); ?>
            </td>
            <td>
                <?php if ($item->is_active == 1): ?>
                    <span class="label label-success"><?php echo _l('active'); ?></span>
                <?php else: ?>
                    <span class="label label-default"><?php echo _l('inactive'); ?></span>
                <?php endif; ?>
            </td>
            <td data-order="<?php echo $item->date_created; ?>">
                <?php echo _dt($item->date_created); ?>
            </td>
            <td>
                <div class="tw-flex tw-items-center tw-space-x-2">
                    <?php if (has_permission('securelogin_guard', '', 'edit')): ?>
                    <a href="#" 
                       class="edit-ip-btn tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
                       data-id="<?php echo $item->id; ?>"
                       data-toggle="modal" 
                       data-target="#editIpModal"
                       data-toggle="tooltip" title="<?php echo _l('edit'); ?>">
                        <i class="fa-regular fa-pen-to-square fa-lg"></i>
                    </a>
                    <?php echo form_open(admin_url('securelogin_guard/toggle/' . $item->id), ['method' => 'post', 'style' => 'display:inline']); ?>
                        <button type="submit" class="btn btn-link p-0 tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" data-toggle="tooltip" title="<?php echo $item->is_active == 1 ? _l('disable') : _l('enable'); ?>">
                            <i class="fa-regular fa-<?php echo $item->is_active == 1 ? 'ban' : 'check'; ?> fa-lg"></i>
                        </button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                    <?php if (has_permission('securelogin_guard', '', 'delete')): ?>
                    <?php echo form_open(admin_url('securelogin_guard/delete/' . $item->id), ['method' => 'post', 'style' => 'display:inline', 'onsubmit' => 'return confirm("' . _l('confirm_delete') . '");']); ?>
                        <button type="submit" class="btn btn-link p-0 tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" data-toggle="tooltip" title="<?php echo _l('delete'); ?>">
                            <i class="fa-regular fa-trash-can fa-lg"></i>
                        </button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>


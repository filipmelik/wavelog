<div class="container mb-4 mt-2">
    <br>
    <h2><?php echo $page_title; ?></h2>
    <div class="card">
        <div class="card-header">
            How it works
        </div>
        <div class="card-body">
            <p class="card-text">
                The Cron Manager assists the administrator in managing cron jobs without requiring CLI access.
            </p>
            <p class="card-text">
                To execute cron jobs based on the data below, remove all old cron jobs and create a new one:
            </p>
            <div class="main_cronjob">
                <pre><code id="main_cronjob">* * * * * curl --silent <?php echo base_url(); ?>index.php/cron/run &>/dev/null</code><span data-bs-toggle="tooltip" title="<?php echo lang('copy_to_clipboard'); ?>" onclick='copyCron("main_cronjob")'><i class="copy-icon fas fa-copy"></i></span></pre>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Cron List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table style="width:100%" class="crontable table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Description</th>
                            <th>Intervall</th>
                            <th>Last Run</th>
                            <th>Next Run</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($crons as $cron) { ?>
                            <tr>
                                <td style="vertical-align: middle;" class='cron_<?php echo $cron->id; ?>'><?php echo $cron->id; ?></td>
                                <td style="vertical-align: middle;"><?php echo $cron->description; ?></td>
                                <td style="vertical-align: middle;" id="humanreadable_tooltip" data-bs-toggle="tooltip"><?php echo '<code>'.$cron->expression.'</code>'; ?></td>
                                <td style="vertical-align: middle;"><?php echo $cron->last_run ?? 'never'; ?></td>
                                <td style="vertical-align: middle;"><?php echo $cron->next_run ?? 'never'; ?></td>
                                <td style="vertical-align: middle;"><button id="<?php echo $cron->id; ?>" class="editCron btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></button></td>
                                <td></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
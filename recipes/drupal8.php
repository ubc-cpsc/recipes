<?php

namespace Deployer;

require_once __DIR__ . '/drupal.php';

add('recipes', ['drupal8']);

add('rsync_untracked_paths', [
    'drush/Commands',
    'public/core',
    'public/libraries',
    'public/modules/contrib',
    'public/profiles/contrib',
    'public/themes/contrib',
]);

desc('Execute drush update & config import');
task('deploy:drush', function () {
    // https://www.drush.org/latest/deploycommand/
    invoke('drush:deploy');
})->once();

desc('Execute drush config:export to backup previous config');
task('drush:config:backup', function() {
    // Skip when there aren't database connection variables.
    if (! test('[ -s {{release_or_current_path}}/.env ]')) {
        writeln("<fg=yellow;options=bold;>Warning: </><fg=yellow;>Your .env file is empty! Skipping...</>");
        return;
    }

    // Skip when there are no tables in the database.
    if (test('[[ -z "$(./vendor/bin/drush sql:query \'SHOW TABLES\')" ]]')) {
        writeln("<fg=yellow;options=bold;>Warning: </><fg=yellow;>Your database is empty! Skipping...</>");
        return;
    }

    $destination = has('previous_release') ? '{{previous_release}}' : '{{release_path}}';
    run("mkdir -p $destination/config/backup");
    cd('{{release_or_current_path}}');
    run("./vendor/bin/drush -y config:export --destination=$destination");
    writeln('Backup saved to ' . $destination);
});
before('deploy:drush', 'drush:config:backup');

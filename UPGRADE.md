# Updating With ZIP on cPanel

New installs store persistent installation files outside the app folder:

```text
../cashback_config.php
../cashback_installed.lock
```

These files should survive when you replace or extract a new ZIP into the app folder.

If the installer says the parent directory is not writable, fix the permission in cPanel or create the files manually in the parent directory. The installer no longer writes these two files inside the app folder, because that folder is the one you replace during ZIP updates.

If your existing install used the old paths, back up these files before replacing files:

```text
config/config.php
storage/installed.lock
```

For best safety, move/copy them to the persistent parent-folder paths:

```text
config/config.php        -> ../cashback_config.php
storage/installed.lock   -> ../cashback_installed.lock
```

After extracting a new ZIP:

1. Open `/login`.
2. Do not rerun `/install.php` on an existing database.
3. If the installer opens, restore the config and lock files above.

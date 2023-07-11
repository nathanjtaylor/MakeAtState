# Configure Ubuntu unattended upgrades
## Optional variables
* `ubuntu_unattended_upgrades_install_updates`: Whether to install updates.
  (Default: true.)
* `ubuntu_unattended_upgrades_install_backports`: Whether to install backports.
  (Default: false.)
* `ubuntu_unattended_upgrades_install_esm`: Whether to install packages from the
  "extended security maintenance" release. (Default: true.)
* `ubuntu_unattended_upgrades_remove_unused`: Whether to remove unneeded
  packages. (Default: false.)
* `ubuntu_unattended_upgrades_reboot`: Whether to reboot when needed. (Default:
  false.)
* `ubuntu_unattended_upgrades_reboot_time`: What time to do needed reboots.
  (Default: 02:00.)
* `ubuntu_unattended_upgrades_email_address`: Destination for notification
  emails. (Default: `root`.)
* `ubuntu_unattended_upgrades_email_quiet`: Whether only to send email for
  problems. (Default: true.)

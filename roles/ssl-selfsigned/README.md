# Simple Ansible role to create a self-signed SSL ceritificate and key

## Required role variables
* `ssl_selfsigned_cert_file`
* `ssl_selfsigned_csr_file`
* `ssl_selfsigned_key_file`

## Optional role variables
* `ssl_selfsigned_name`: The common name for the certificate. Defaults to the
  value of `inventory_hostname`.
* `ssl_selfsigned_validity`: The validity period. Defaults to 3650 days.

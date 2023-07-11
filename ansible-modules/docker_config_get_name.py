#!/usr/bin/python
# -*- coding: utf-8 -*-

DOCUMENTATION = '''
---
module: docker_config_get_name
author: Renee Margaret McConahy (@nepella)
short_description: Finds whether services in a Docker stack are healthy
description:
  This uses the C(docker stack services) command to get information about the
  services in the specified stack. It returns success if the number of running
  replicas for each service is the same as the number of requested replicas.
options:
  name:
    description:
      - Stack name.
    type: str
    required: yes
'''

RETURN = '''
results:
  description: A list of unhealthy services, each described by a dictionary
  sample: [{"name":"web","replicas_online":0,"replicas_requested":1}]
  returned: unless the Docker command fails
  type: list
  elements: dict
stdout:
  description: STDOUT from the Docker command
  type: str
  returned: always
stderr:
  description: STDERR from the Docker command
  type: str
  returned: when the Docker command fails
rc:
  description: The return code from the Docker command
  type: int
  returned: when the Docker command fails
'''

EXAMPLES = '''
- name: Check whether all of the stack's services are healthy.
  docker_stack_healthy:
    name: nagios
  register: _r
'''

import json
from ansible.module_utils.basic import AnsibleModule

def docker_config_get_name(module, stack_name, service_name):
    docker_bin = module.get_bin_path('docker', required=True)
    rc, out, err = module.run_command([docker_bin, "service", "inspect",
        stack_name + "_" + service_name, "--format={{json .}}"])

    return rc, out.strip(), err.strip()

def main():
    module = AnsibleModule(
        argument_spec={
            'stack': dict(type='str', required=True),
            'service': dict(type='str', required=True),
            'path': dict(type='str', required=True)
        # FIXME: Handle both config and secret.
        },
        supports_check_mode=True
    )

    rc, out, err = docker_config_get_name(module,
                                          module.params['stack'],
                                          module.params['service'])

    if rc != 0:
        # FIXME: Handle 'Status: Error: no such service: nagios_cgie, Code: 1'
        module.fail_json(msg="Error running docker stack. {0}".format(err),
                         rc=rc, stdout=out, stderr=err)
    else:
        # FIXME: Handle path not found!
        # FIXME: Handle more than one array element.
        # FIXME: Handle JSON path not found?

        secrets = (json.loads(out)
                       .get('Spec', {})
                       .get('TaskTemplate', {})
                       .get('ContainerSpec', {})
                       .get('Secrets', {}))

        secret_name = next(
            iter(
                s.get('SecretName') for s in secrets
                if s.get('File', {}).get('Name') == module.params['path']),
            None)

        if not secret_name:
            module.fail_json(msg='No such secret')

        module.exit_json(changed=False,
                         name=secret_name)

if __name__ == "__main__":
    main()

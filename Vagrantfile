machine_name = "3dprime.test"

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-20.04"
  config.vm.hostname = machine_name
  config.vm.define machine_name
  config.vm.network "private_network", ip: "192.168.20.160"
  config.vm.synced_folder ".", "/vagrant", disabled: true
  config.vm.provider "virtualbox" do |vb|
    vb.linked_clone = true
    vb.memory = 2048
    # The following settings are to remedy a bug in the Ubuntu image that
    # causes booting to take minutes when there is no serial port. See:
    # https://github.com/hashicorp/vagrant/issues/11777#issuecomment-661076612
    vb.customize ["modifyvm", :id, "--uart1", "0x3F8", "4"]
    vb.customize ["modifyvm", :id, "--uartmode1", "file", File::NULL]
  end

  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "playbook.yml"
    ansible.compatibility_mode = "2.0"
    ansible.groups = {
      "makeatstate": machine_name,
      "vagrant": machine_name,
    }
  end
end

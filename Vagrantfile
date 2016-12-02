def apply_ansible_defaults(ansible)
    ansible.inventory_path = "ansible/inventories/dev"
    ansible.limit = 'dev'
end

Vagrant.configure("2") do |config|
    config.vm.provider :virtualbox do |v|
        v.name = "snaprapid-dashboards-api"
        v.customize [
            "modifyvm", :id,
            "--name", v.name,
            "--memory", 2048,
            "--natdnshostresolver1", "on",
            "--cpus", 1,
        ]
    end

    config.vm.box = "debian/jessie64"

    config.vm.network :private_network, ip: "192.168.42.152"
    config.ssh.forward_agent = true

    config.vm.provision "provision", type: "ansible" do |ansible|
        apply_ansible_defaults(ansible)
        ansible.playbook = "ansible/provision.yml"
    end

    config.vm.provision "up", type: "ansible", run: "always" do |ansible|
        apply_ansible_defaults(ansible)
        ansible.playbook = "ansible/vagrant-up.yml"
    end

    config.vm.synced_folder "./", "/vagrant", type: "nfs", mount_options: ["actimeo=2"]
end

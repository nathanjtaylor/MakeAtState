{ pkgs ? import <nixpkgs> {} }:
  pkgs.mkShell {
    buildInputs = [
        pkgs.ansible_2_9
        pkgs.python38Packages.pyopenssl
        # Nota bene: This requires the host VirtualBox package, but "virtualbox"
        # should not be added here.
        pkgs.vagrant
    ];
}

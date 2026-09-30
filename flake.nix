{
  description = "ads-slider: digital signage software based on Laravel";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils, ... }:
    {
      overlays.default = final: prev: {
        ads-slider = final.callPackage ./nix/package.nix { };
      };

      nixosModules.default = import ./nix/module.nix self;
      nixosModules.ads-slider = self.nixosModules.default;
    }
    // flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
      in
      {
        packages = rec {
          ads-slider = pkgs.callPackage ./nix/package.nix { };
          default = ads-slider;
        };

        devShells.default = pkgs.mkShell {
          packages = [
            (pkgs.php84.withExtensions ({ enabled, all }: enabled ++ [ all.pdo_mysql all.pcntl all.gd ]))
            pkgs.php84Packages.composer
            pkgs.nodejs
          ];
        };
      });
}

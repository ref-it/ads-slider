{ lib
, php84
, nodejs
, fetchNpmDeps
, npmHooks
, # Directory holding all mutable state at runtime (storage/, bootstrap cache).
  dataDir ? "/var/lib/ads-slider"
, # VITE_* variables are compiled into the frontend bundle, so they have to be
  # known at build time. The NixOS module sets them via `override`.
  viteEnv ? { }
}:

let
  php = php84.withExtensions ({ enabled, all }: enabled ++ [
    all.bcmath
    all.pdo_mysql
    all.pcntl
    all.gd
    all.intl
  ]);
in
php.buildComposerProject2 (finalAttrs: {
  pname = "ads-slider";
  version = "0.0.0";

  src = lib.cleanSourceWith {
    src = lib.cleanSource ../.;
    filter = path: type:
      let base = baseNameOf path; in
      !(builtins.elem base [ "nix" "flake.nix" "flake.lock" "node_modules" "vendor" ".env" ".github" ".idea" ".devcontainer" "docs" ]);
  };

  composerStrictValidation = false;

  vendorHash = "sha256-jYbDHyYtSDaVRsSIhRySS18hWbkrAO/kdoOcTgY5PTc=";

  npmDeps = fetchNpmDeps {
    inherit (finalAttrs) src;
    name = "${finalAttrs.pname}-npm-deps";
    hash = "sha256-fxV8N+9IL0LlpeXudVsKkuXvmVJY1iRa4qfIphyeLAo=";
  };

  nativeBuildInputs = [ nodejs npmHooks.npmConfigHook ];

  env = lib.mapAttrs (_: toString) viteEnv;

  postBuild = ''
    npm run build
  '';

  postInstall = ''
    cd $out/share/php/ads-slider

    # Everything writable lives outside the store.
    rm -rf storage bootstrap/cache
    ln -s ${dataDir}/storage storage
    ln -s ${dataDir}/bootstrap-cache bootstrap/cache
    ln -s ${dataDir}/storage/app/public public/storage

    # Not needed at runtime.
    rm -rf node_modules resources/js resources/sass tests
  '';

  passthru = { inherit php; };

  meta = {
    description = "Digital signage software based on Laravel";
    license = lib.licenses.eupl12;
    
  };
})

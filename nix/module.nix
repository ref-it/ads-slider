self:
{ config, lib, pkgs, ... }:

let
  inherit (lib) mkEnableOption mkOption mkIf mkMerge mkDefault types;

  cfg = config.services.ads-slider;
  pool = "ads-slider";

  package = cfg.package.override {
    dataDir = cfg.dataDir;
    viteEnv = {
      VITE_APP_URL = "https://${cfg.domain}";
      VITE_APP_ENV = "production";
      VITE_BRAND_NAME = cfg.brandName;
      VITE_REVERB_APP_KEY = cfg.reverb.appKey;
      VITE_REVERB_HOST = cfg.domain;
      VITE_REVERB_PORT = 443;
      VITE_REVERB_SCHEME = "https";
    };
  };

  appRoot = "${package}/share/php/ads-slider";
  php = package.passthru.php;
  artisan = "${php}/bin/php ${appRoot}/artisan";

  settingsType = with types; attrsOf (oneOf [ str int bool ]);
  envOf = lib.mapAttrs (_: v: if lib.isBool v then lib.boolToString v else toString v);

  # Non-secret configuration. Secrets (APP_KEY, REVERB_APP_SECRET, ...) go in `environmentFile`.
  environment = envOf cfg.settings;

  commonServiceConfig = {
    User = cfg.user;
    Group = cfg.group;
    EnvironmentFile = [ cfg.environmentFile ];
    WorkingDirectory = appRoot;
    StateDirectory = lib.removePrefix "/var/lib/" cfg.dataDir;
    NoNewPrivileges = true;
    PrivateTmp = true;
    ProtectSystem = "strict";
    ProtectHome = true;
    ReadWritePaths = [ cfg.dataDir ];
  };

  dbService = lib.optional cfg.database.createLocally "mysql.service";
in
{
  options.services.ads-slider = {
    enable = mkEnableOption "ads-slider, a digital signage software based on Laravel";

    package = mkOption {
      type = types.package;
      default = self.packages.${pkgs.stdenv.hostPlatform.system}.default;
      defaultText = lib.literalExpression "ads-slider.packages.\${system}.default";
      description = ''
        The ads-slider package. It is overridden with `dataDir` and the
        frontend build variables derived from this module's options.
      '';
    };

    domain = mkOption {
      type = types.str;
      example = "slider.example.org";
      description = "Public FQDN. Also compiled into the frontend (Reverb host, app URL).";
    };

    brandName = mkOption {
      type = types.str;
      default = "";
      description = "Brand name shown in the browser title (VITE_BRAND_NAME, build time).";
    };

    user = mkOption { type = types.str; default = "ads-slider"; };
    group = mkOption { type = types.str; default = "ads-slider"; };

    dataDir = mkOption {
      type = types.str;
      default = "/var/lib/ads-slider";
      description = "Mutable state (uploads, logs, caches). Must be below /var/lib.";
    };

    environmentFile = mkOption {
      type = types.path;
      example = "/run/secrets/ads-slider-env";
      description = ''
        Environment file with secrets in `KEY=value` format. Required:
        `APP_KEY` (`base64:` + 32 random bytes) and `REVERB_APP_SECRET`.
        Optional: `OW_API_KEY`, `OIDC_CLIENT_SECRET`, mail credentials, ...
      '';
    };

    settings = mkOption {
      type = settingsType;
      default = { };
      example = { APP_LOCALE = "de"; OIDC_ENABLED = true; };
      description = "Additional non-secret environment variables (see `.env.example`).";
    };

    reverb = {
      appKey = mkOption {
        type = types.str;
        default = "ads-slider";
        description = ''
          Public Reverb app key. It is part of the frontend bundle and therefore
          not secret; the matching secret goes in `environmentFile`.
        '';
      };
      appId = mkOption { type = types.str; default = "ads-slider"; };
      port = mkOption {
        type = types.port;
        default = 8080;
        description = "Local port of the Reverb websocket server (proxied by nginx).";
      };
    };

    database = {
      createLocally = mkOption {
        type = types.bool;
        default = true;
        description = "Provision a local MariaDB database, accessed via unix socket.";
      };
      name = mkOption { type = types.str; default = "ads_slider"; };
    };

    maxUploadSize = mkOption {
      type = types.str;
      default = "256M";
      description = "Maximum upload size (PHP and nginx).";
    };

    nginx.virtualHost = mkOption {
      type = types.attrs;
      default = { enableACME = true; forceSSL = true; };
      example = lib.literalExpression ''{ sslCertificate = "/run/secrets/tls-cert"; sslCertificateKey = "/run/secrets/tls-cert-key"; forceSSL = true; }'';
      description = "Extra options merged into the nginx virtual host (TLS setup, access rules, ...).";
    };
  };

  config = mkIf cfg.enable (mkMerge [
    {
      services.ads-slider.settings = lib.mapAttrs (_: mkDefault) ({
        APP_NAME = "AdsSlider";
        APP_ENV = "production";
        APP_DEBUG = false;
        APP_URL = "https://${cfg.domain}";
        LOG_CHANNEL = "stack";
        CACHE_DRIVER = "file";
        SESSION_DRIVER = "file";
        QUEUE_CONNECTION = "database";
        BROADCAST_DRIVER = "reverb";

        REVERB_APP_KEY = cfg.reverb.appKey;
        REVERB_APP_ID = cfg.reverb.appId;
        REVERB_HOST = cfg.domain;
        REVERB_PORT = 443;
        REVERB_SCHEME = "https";
        REVERB_SERVER_HOST = "127.0.0.1";
        REVERB_SERVER_PORT = cfg.reverb.port;

        # The package directory is read-only, keep Laravel's caches in the state dir.
        APP_SERVICES_CACHE = "${cfg.dataDir}/bootstrap-cache/services.php";
        APP_PACKAGES_CACHE = "${cfg.dataDir}/bootstrap-cache/packages.php";
        APP_CONFIG_CACHE = "${cfg.dataDir}/bootstrap-cache/config.php";
        APP_ROUTES_CACHE = "${cfg.dataDir}/bootstrap-cache/routes-v7.php";
        APP_EVENTS_CACHE = "${cfg.dataDir}/bootstrap-cache/events.php";
      } // lib.optionalAttrs cfg.database.createLocally {
        DB_CONNECTION = "mysql";
        DB_SOCKET = "/run/mysqld/mysqld.sock";
        DB_DATABASE = cfg.database.name;
        DB_USERNAME = cfg.user;
        DB_PASSWORD = "";
      });

      users.users.${cfg.user} = {
        isSystemUser = true;
        group = cfg.group;
        home = cfg.dataDir;
      };
      users.groups.${cfg.group} = { };

      systemd.tmpfiles.rules = map (d: "d ${cfg.dataDir}/${d} 0750 ${cfg.user} ${cfg.group} - -") [
        ""
        "bootstrap-cache"
        "storage"
        "storage/app"
        "storage/app/public"
        "storage/framework"
        "storage/framework/cache"
        "storage/framework/cache/data"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
      ];

      # Admin helper: `ads-slider-artisan tinker`, `ads-slider-artisan user:create`, ...
      environment.systemPackages = [
        (pkgs.writeShellScriptBin "ads-slider-artisan" ''
          exec systemd-run --quiet --pipe --wait --collect \
            -p User=${cfg.user} -p Group=${cfg.group} \
            -p EnvironmentFile=${cfg.environmentFile} \
            ${lib.concatStringsSep " " (lib.mapAttrsToList (k: v: "-E ${k}=${lib.escapeShellArg v}") environment)} \
            -p WorkingDirectory=${appRoot} \
            ${artisan} "$@"
        '')
      ];

      services.phpfpm.pools.${pool} = {
        user = cfg.user;
        group = cfg.group;
        phpPackage = php;
        settings = {
          "listen.owner" = config.services.nginx.user;
          "listen.group" = config.services.nginx.group;
          "pm" = "dynamic";
          "pm.max_children" = 16;
          "pm.start_servers" = 2;
          "pm.min_spare_servers" = 1;
          "pm.max_spare_servers" = 4;
          # Let workers see the systemd environment (EnvironmentFile with secrets).
          "clear_env" = "no";
        };
        phpOptions = ''
          upload_max_filesize = ${cfg.maxUploadSize}
          post_max_size = ${cfg.maxUploadSize}
          memory_limit = 512M
        '';
      };

      systemd.services."phpfpm-${pool}" = {
        inherit environment;
        after = dbService;
        requires = dbService;
        serviceConfig.EnvironmentFile = [ cfg.environmentFile ];
      };

      services.nginx = {
        enable = true;
        virtualHosts.${cfg.domain} = mkMerge [
          cfg.nginx.virtualHost
          {
            root = "${appRoot}/public";
            extraConfig = ''
              client_max_body_size ${cfg.maxUploadSize};
              index index.php;
            '';
            locations."/" = {
              tryFiles = "$uri $uri/ /index.php?$query_string";
            };
            locations."~ \\.php$".extraConfig = ''
              include ${config.services.nginx.package}/conf/fastcgi_params;
              fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
              fastcgi_pass unix:${config.services.phpfpm.pools.${pool}.socket};
            '';
            # Laravel Reverb (websockets)
            locations."/app" = {
              proxyPass = "http://127.0.0.1:${toString cfg.reverb.port}";
              proxyWebsockets = true;
              recommendedProxySettings = true;
            };
            locations."/apps" = {
              proxyPass = "http://127.0.0.1:${toString cfg.reverb.port}";
              recommendedProxySettings = true;
            };
            locations."~ /\\.(?!well-known)".extraConfig = "deny all;";
          }
        ];
      };

      systemd.services.ads-slider-setup = {
        description = "ads-slider database migrations";
        wantedBy = [ "multi-user.target" ];
        before = [ "phpfpm-${pool}.service" "ads-slider-queue.service" "ads-slider-reverb.service" ];
        after = dbService;
        requires = dbService;
        inherit environment;
        restartTriggers = [ package ];
        serviceConfig = commonServiceConfig // {
          Type = "oneshot";
          RemainAfterExit = true;
          ExecStart = "${artisan} migrate --force";
        };
      };

      systemd.services.ads-slider-queue = {
        description = "ads-slider queue worker";
        wantedBy = [ "multi-user.target" ];
        after = [ "ads-slider-setup.service" ];
        requires = [ "ads-slider-setup.service" ];
        inherit environment;
        restartTriggers = [ package ];
        serviceConfig = commonServiceConfig // {
          ExecStart = "${artisan} queue:work --tries=3 --max-time=3600";
          Restart = "always";
          RestartSec = 5;
        };
      };

      systemd.services.ads-slider-reverb = {
        description = "ads-slider Reverb websocket server";
        wantedBy = [ "multi-user.target" ];
        after = [ "ads-slider-setup.service" ];
        requires = [ "ads-slider-setup.service" ];
        inherit environment;
        restartTriggers = [ package ];
        serviceConfig = commonServiceConfig // {
          ExecStart = "${artisan} reverb:start --no-interaction";
          Restart = "always";
          RestartSec = 5;
          LimitNOFILE = 16384;
        };
      };

      systemd.services.ads-slider-schedule = {
        description = "ads-slider scheduler (weather, events, canteens, ...)";
        after = [ "ads-slider-setup.service" ];
        requires = [ "ads-slider-setup.service" ];
        inherit environment;
        serviceConfig = commonServiceConfig // {
          Type = "oneshot";
          ExecStart = "${artisan} schedule:run";
        };
      };
      systemd.timers.ads-slider-schedule = {
        wantedBy = [ "timers.target" ];
        timerConfig = {
          OnCalendar = "minutely";
          AccuracySec = "1s";
        };
      };
    }

    (mkIf cfg.database.createLocally {
      services.mysql = {
        enable = true;
        package = mkDefault pkgs.mariadb;
        ensureDatabases = [ cfg.database.name ];
        ensureUsers = [{
          name = cfg.user;
          ensurePermissions = { "${cfg.database.name}.*" = "ALL PRIVILEGES"; };
        }];
      };
    })
  ]);
}

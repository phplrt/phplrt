<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Parser;

#[Warmup(2)]
#[Revs(3)]
#[Iterations(4)]
#[RetryThreshold(0.3)]
#[BeforeMethods('prepare')]
final readonly class PhplrtTracingBench
{
    private const string SAMPLE = <<<'PHP'
        array{
            secret?: scalar|Param|null,
            http_method_override?: bool|Param, // Set true to enable support for the '_method' request parameter to determine the intended HTTP method on POST requests. // Default: false
            allowed_http_method_override?: null|list<string|Param>,
            trust_x_sendfile_type_header?: scalar|Param|null, // Set true to enable support for xsendfile in binary file responses. // Default: "%env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)%"
            ide?: scalar|Param|null, // Default: "%env(default::SYMFONY_IDE)%"
            test?: bool|Param,
            default_locale?: scalar|Param|null, // Default: "en"
            set_locale_from_accept_language?: bool|Param, // Whether to use the Accept-Language HTTP header to set the Request locale (only when the "_locale" request attribute is not passed). // Default: false
            set_content_language_from_locale?: bool|Param, // Whether to set the Content-Language HTTP header on the Response using the Request locale. // Default: false
            enabled_locales?: list<scalar|Param|null>,
            trusted_hosts?: string|list<scalar|Param|null>,
            trusted_proxies?: mixed, // Default: ["%env(default::SYMFONY_TRUSTED_PROXIES)%"]
            trusted_headers?: string|list<scalar|Param|null>,
            error_controller?: scalar|Param|null, // Default: "error_controller"
            handle_all_throwables?: bool|Param, // HttpKernel will handle all kinds of \Throwable. // Default: true
            csrf_protection?: bool|array{
                enabled?: scalar|Param|null, // Default: null
                stateless_token_ids?: list<scalar|Param|null>,
                check_header?: scalar|Param|null, // Whether to check the CSRF token in a header in addition to a cookie when using stateless protection. // Default: false
                cookie_name?: scalar|Param|null, // The name of the cookie to use when using stateless protection. // Default: "csrf-token"
            },
            form?: bool|array{ // Form configuration
                enabled?: bool|Param, // Default: false
                csrf_protection?: bool|array{
                    enabled?: scalar|Param|null, // Default: null
                    token_id?: scalar|Param|null, // Default: null
                    field_name?: scalar|Param|null, // Default: "_token"
                    field_attr?: array<string, scalar|Param|null>,
                },
            },
            http_cache?: bool|array{ // HTTP cache configuration
                enabled?: bool|Param, // Default: false
                debug?: bool|Param, // Default: "%kernel.debug%"
                trace_level?: "none"|"short"|"full"|Param,
                trace_header?: scalar|Param|null,
                default_ttl?: int|Param,
                private_headers?: list<scalar|Param|null>,
                skip_response_headers?: list<scalar|Param|null>,
                allow_reload?: bool|Param,
                allow_revalidate?: bool|Param,
                stale_while_revalidate?: int|Param,
                stale_if_error?: int|Param,
                terminate_on_cache_hit?: bool|Param, // Deprecated: Setting the "framework.http_cache.terminate_on_cache_hit.terminate_on_cache_hit" configuration option is deprecated. It will be removed in version 9.0.
            },
            esi?: bool|array{ // ESI configuration
                enabled?: bool|Param, // Default: false
            },
            ssi?: bool|array{ // SSI configuration
                enabled?: bool|Param, // Default: false
            },
            fragments?: bool|array{ // Fragments configuration
                enabled?: bool|Param, // Default: false
                hinclude_default_template?: scalar|Param|null, // Default: null
                path?: scalar|Param|null, // Default: "/_fragment"
            },
            profiler?: bool|array{ // Profiler configuration
                enabled?: bool|Param, // Default: false
                collect?: bool|Param, // Default: true
                collect_parameter?: scalar|Param|null, // The name of the parameter to use to enable or disable collection on a per request basis. // Default: null
                only_exceptions?: bool|Param, // Default: false
                only_main_requests?: bool|Param, // Default: false
                dsn?: scalar|Param|null, // Default: "file:%kernel.cache_dir%/profiler"
                collect_serializer_data?: true|Param, // Deprecated: Setting the "framework.profiler.collect_serializer_data.collect_serializer_data" configuration option is deprecated. It will be removed in version 9.0. // Default: true
            },
            workflows?: bool|array{
                enabled?: bool|Param, // Default: false
                workflows?: array<string, array{ // Default: []
                    audit_trail?: bool|array{
                        enabled?: bool|Param, // Default: false
                    },
                    type?: "workflow"|"state_machine"|Param, // Default: "state_machine"
                    marking_store?: array{
                        type?: "method"|Param,
                        property?: scalar|Param|null,
                        service?: scalar|Param|null,
                    },
                    supports?: string|list<scalar|Param|null>,
                    definition_validators?: list<scalar|Param|null>,
                    support_strategy?: scalar|Param|null,
                    initial_marking?: \BackedEnum|string|list<scalar|Param|null>,
                    events_to_dispatch?: null|list<string|Param>,
                    places?: string|list<array{ // Default: []
                        name?: scalar|Param|null,
                        metadata?: array<string, mixed>,
                    }>,
                    transitions?: list<array{ // Default: []
                        name?: string|Param,
                        guard?: string|Param, // An expression to block the transition.
                        from?: \BackedEnum|string|list<array{ // Default: []
                            place?: string|Param,
                            weight?: int|Param, // Default: 1
                        }>,
                        to?: \BackedEnum|string|list<array{ // Default: []
                            place?: string|Param,
                            weight?: int|Param, // Default: 1
                        }>,
                        weight?: int|Param, // Default: 1
                        metadata?: array<string, mixed>,
                    }>,
                    metadata?: array<string, mixed>,
                }>,
            },
            router?: bool|array{ // Router configuration
                enabled?: bool|Param, // Default: false
                resource?: scalar|Param|null,
                type?: scalar|Param|null,
                default_uri?: scalar|Param|null, // The default URI used to generate URLs in a non-HTTP context. // Default: null
                http_port?: scalar|Param|null, // Default: 80
                https_port?: scalar|Param|null, // Default: 443
                strict_requirements?: scalar|Param|null, // set to true to throw an exception when a parameter does not match the requirements set to false to disable exceptions when a parameter does not match the requirements (and return null instead) set to null to disable parameter checks against requirements 'true' is the preferred configuration in development mode, while 'false' or 'null' might be preferred in production // Default: true
                utf8?: bool|Param, // Default: true
            },
            session?: bool|array{ // Session configuration
                enabled?: bool|Param, // Default: false
                storage_factory_id?: scalar|Param|null, // Default: "session.storage.factory.native"
                handler_id?: scalar|Param|null, // Defaults to using the native session handler, or to the native *file* session handler if "save_path" is not null.
                name?: scalar|Param|null,
                cookie_lifetime?: scalar|Param|null,
                cookie_path?: scalar|Param|null,
                cookie_domain?: scalar|Param|null,
                cookie_secure?: true|false|"auto"|Param, // Default: "auto"
                cookie_httponly?: bool|Param, // Default: true
                cookie_samesite?: null|"lax"|"strict"|"none"|Param, // Default: "lax"
                use_cookies?: bool|Param,
                gc_divisor?: scalar|Param|null,
                gc_probability?: scalar|Param|null,
                gc_maxlifetime?: scalar|Param|null,
                save_path?: scalar|Param|null, // Defaults to "%kernel.cache_dir%/sessions" if the "handler_id" option is not null.
                metadata_update_threshold?: int|Param, // Seconds to wait between 2 session metadata updates. // Default: 0
            },
            request?: bool|array{ // Request configuration
                enabled?: bool|Param, // Default: false
                formats?: array<string, string|list<scalar|Param|null>>,
            },
            assets?: bool|array{ // Assets configuration
                enabled?: bool|Param, // Default: true
                strict_mode?: bool|Param, // Throw an exception if an entry is missing from the manifest.json. // Default: false
                version_strategy?: scalar|Param|null, // Default: null
                version?: scalar|Param|null, // Default: null
                version_format?: scalar|Param|null, // Default: "%%s?%%s"
                json_manifest_path?: scalar|Param|null, // Default: null
                base_path?: scalar|Param|null, // Default: ""
                base_urls?: string|list<scalar|Param|null>,
                packages?: array<string, array{ // Default: []
                    strict_mode?: bool|Param, // Throw an exception if an entry is missing from the manifest.json. // Default: false
                    version_strategy?: scalar|Param|null, // Default: null
                    version?: scalar|Param|null,
                    version_format?: scalar|Param|null, // Default: null
                    json_manifest_path?: scalar|Param|null, // Default: null
                    base_path?: scalar|Param|null, // Default: ""
                    base_urls?: string|list<scalar|Param|null>,
                }>,
            },
            asset_mapper?: bool|array{ // Asset Mapper configuration
                enabled?: bool|Param, // Default: false
                paths?: string|array<string, scalar|Param|null>,
                excluded_patterns?: list<scalar|Param|null>,
                exclude_dotfiles?: bool|Param, // If true, any files starting with "." will be excluded from the asset mapper. // Default: true
                server?: bool|Param, // If true, a "dev server" will return the assets from the public directory (true in "debug" mode only by default). // Default: true
                public_prefix?: scalar|Param|null, // The public path where the assets will be written to (and served from when "server" is true). // Default: "/assets/"
                missing_import_mode?: "strict"|"warn"|"ignore"|Param, // Behavior if an asset cannot be found when imported from JavaScript or CSS files - e.g. "import './non-existent.js'". "strict" means an exception is thrown, "warn" means a warning is logged, "ignore" means the import is left as-is. // Default: "warn"
                extensions?: array<string, scalar|Param|null>,
                importmap_path?: scalar|Param|null, // The path of the importmap.php file. // Default: "%kernel.project_dir%/importmap.php"
                importmap_polyfill?: scalar|Param|null, // The importmap name that will be used to load the polyfill. Set to false to disable. // Default: "es-module-shims"
                importmap_script_attributes?: array<string, scalar|Param|null>,
                vendor_dir?: scalar|Param|null, // The directory to store JavaScript vendors. // Default: "%kernel.project_dir%/assets/vendor"
                precompress?: bool|array{ // Precompress assets with Brotli, Zstandard and gzip.
                    enabled?: bool|Param, // Default: false
                    formats?: list<scalar|Param|null>,
                    extensions?: list<scalar|Param|null>,
                },
            },
            translator?: bool|array{ // Translator configuration
                enabled?: bool|Param, // Default: true
                fallbacks?: string|list<scalar|Param|null>,
                logging?: bool|Param, // Default: false
                formatter?: scalar|Param|null, // Default: "translator.formatter.default"
                cache_dir?: scalar|Param|null, // Default: "%kernel.cache_dir%/translations"
                default_path?: scalar|Param|null, // The default path used to load translations. // Default: "%kernel.project_dir%/translations"
                paths?: list<scalar|Param|null>,
                pseudo_localization?: bool|array{
                    enabled?: bool|Param, // Default: false
                    accents?: bool|Param, // Default: true
                    expansion_factor?: float|Param, // Default: 1.0
                    brackets?: bool|Param, // Default: true
                    parse_html?: bool|Param, // Default: false
                    localizable_html_attributes?: list<scalar|Param|null>,
                },
                providers?: array<string, array{ // Default: []
                    dsn?: scalar|Param|null,
                    domains?: list<scalar|Param|null>,
                    locales?: list<scalar|Param|null>,
                }>,
                globals?: array<string, string|array{ // Default: []
                    value?: mixed,
                    message?: string|Param,
                    parameters?: array<string, scalar|Param|null>,
                    domain?: string|Param,
                }>,
            },
            validation?: bool|array{ // Validation configuration
                enabled?: bool|Param, // Default: true
                enable_attributes?: bool|Param, // Default: true
                static_method?: string|list<scalar|Param|null>,
                translation_domain?: scalar|Param|null, // Default: "validators"
                email_validation_mode?: "html5"|"html5-allow-no-tld"|"strict"|Param, // Default: "html5"
                mapping?: array{
                    paths?: list<scalar|Param|null>,
                },
                not_compromised_password?: bool|array{
                    enabled?: bool|Param, // When disabled, compromised passwords will be accepted as valid. // Default: true
                    endpoint?: scalar|Param|null, // API endpoint for the NotCompromisedPassword Validator. // Default: null
                },
                disable_translation?: bool|Param, // Default: false
                property_metadata_existence_check?: bool|Param, // When enabled, validateProperty() and validatePropertyValue() throw an exception if no metadata is found for the given property. // Default: false
                auto_mapping?: array<string, array{ // Default: []
                    services?: list<scalar|Param|null>,
                }>,
            },
            serializer?: bool|array{ // Serializer configuration
                enabled?: bool|Param, // Default: false
                enable_attributes?: bool|Param, // Default: true
                name_converter?: scalar|Param|null,
                circular_reference_handler?: scalar|Param|null,
                max_depth_handler?: scalar|Param|null,
                mapping?: array{
                    paths?: list<scalar|Param|null>,
                },
                default_context?: array<string, mixed>,
                named_serializers?: array<string, array{ // Default: []
                    name_converter?: scalar|Param|null,
                    default_context?: array<string, mixed>,
                    include_built_in_normalizers?: bool|Param, // Whether to include the built-in normalizers // Default: true
                    include_built_in_encoders?: bool|Param, // Whether to include the built-in encoders // Default: true
                }>,
            },
            property_access?: bool|array{ // Property access configuration
                enabled?: bool|Param, // Default: true
                magic_call?: bool|Param, // Default: false
                magic_get?: bool|Param, // Default: true
                magic_set?: bool|Param, // Default: true
                throw_exception_on_invalid_index?: bool|Param, // Default: false
                throw_exception_on_invalid_property_path?: bool|Param, // Default: true
            },
            type_info?: bool|array{ // Type info configuration
                enabled?: bool|Param, // Default: true
                aliases?: array<string, scalar|Param|null>,
            },
            property_info?: bool|array{ // Property info configuration
                enabled?: bool|Param, // Default: true
                with_constructor_extractor?: bool|Param, // Registers the constructor extractor. // Default: true
            },
            cache?: array{ // Cache configuration
                prefix_seed?: scalar|Param|null, // Used to namespace cache keys when using several apps with the same shared backend. // Default: "_%kernel.project_dir%.%kernel.container_class%"
                app?: scalar|Param|null, // App related cache pools configuration. // Default: "cache.adapter.filesystem"
                system?: scalar|Param|null, // System related cache pools configuration. // Default: "cache.adapter.system"
                directory?: scalar|Param|null, // Default: "%kernel.share_dir%/pools/app"
                default_psr6_provider?: scalar|Param|null,
                default_redis_provider?: scalar|Param|null, // Default: "redis://localhost"
                default_valkey_provider?: scalar|Param|null, // Default: "valkey://localhost"
                default_memcached_provider?: scalar|Param|null, // Default: "memcached://localhost"
                default_doctrine_dbal_provider?: scalar|Param|null, // Default: "database_connection"
                default_pdo_provider?: scalar|Param|null, // Default: null
                pools?: array<string, array{ // Default: []
                    adapters?: string|list<scalar|Param|null>,
                    tags?: scalar|Param|null, // Default: null
                    public?: bool|Param, // Default: false
                    default_lifetime?: scalar|Param|null, // Default lifetime of the pool.
                    provider?: scalar|Param|null, // Overwrite the setting from the default provider for this adapter.
                    early_expiration_message_bus?: scalar|Param|null,
                    clearer?: scalar|Param|null,
                    marshaller?: scalar|Param|null, // The marshaller service to use for this pool.
                }>,
            },
            php_errors?: array{ // PHP errors handling configuration
                log?: mixed, // Use the application logger instead of the PHP logger for logging PHP errors. // Default: true
                throw?: bool|Param, // Throw PHP errors as \ErrorException instances. // Default: true
            },
            exceptions?: array<string, array{ // Default: []
                log_level?: scalar|Param|null, // The level of log message. Null to let Symfony decide. // Default: null
                status_code?: scalar|Param|null, // The status code of the response. Null or 0 to let Symfony decide. // Default: null
                log_channel?: scalar|Param|null, // The channel of log message. Null to let Symfony decide. // Default: null
            }>,
            web_link?: bool|array{ // Web links configuration
                enabled?: bool|Param, // Default: false
            },
            lock?: bool|string|array{ // Lock configuration
                enabled?: bool|Param, // Default: false
                resources?: string|array<string, string|list<scalar|Param|null>>,
            },
            semaphore?: bool|string|array{ // Semaphore configuration
                enabled?: bool|Param, // Default: false
                resources?: string|array<string, scalar|Param|null>,
            },
            messenger?: bool|array{ // Messenger configuration
                enabled?: bool|Param, // Default: true
                routing?: array<string, string|list<scalar|Param|null>>,
                serializer?: array{
                    default_serializer?: scalar|Param|null, // Service id to use as the default serializer for the transports. // Default: "messenger.transport.native_php_serializer"
                    symfony_serializer?: array{
                        format?: scalar|Param|null, // Serialization format for the messenger.transport.symfony_serializer service (which is not the serializer used by default). // Default: "json"
                        context?: array<string, mixed>,
                    },
                },
                transports?: array<string, string|array{ // Default: []
                    dsn?: scalar|Param|null,
                    serializer?: scalar|Param|null, // Service id of a custom serializer to use. // Default: null
                    options?: array<string, mixed>,
                    failure_transport?: scalar|Param|null, // Transport name to send failed messages to (after all retries have failed). // Default: null
                    retry_strategy?: string|array{
                        service?: scalar|Param|null, // Service id to override the retry strategy entirely. // Default: null
                        max_retries?: int|Param, // Default: 3
                        delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
                        multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: this delay = (delay(multiple ^ retries)). // Default: 2
                        max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
                        jitter?: float|Param, // Randomness to apply to the delay (between 0 and 1). // Default: 0.1
                    },
                    rate_limiter?: scalar|Param|null, // Rate limiter name to use when processing messages. // Default: null
                }>,
                failure_transport?: scalar|Param|null, // Transport name to send failed messages to (after all retries have failed). // Default: null
                stop_worker_on_signals?: int|string|list<scalar|Param|null>,
                default_bus?: scalar|Param|null, // Default: null
                buses?: array<string, array{ // Default: {"messenger.bus.default":{"default_middleware":{"enabled":true,"allow_no_handlers":false,"allow_no_senders":true},"middleware":[]}}
                    default_middleware?: bool|string|array{
                        enabled?: bool|Param, // Default: true
                        allow_no_handlers?: bool|Param, // Default: false
                        allow_no_senders?: bool|Param, // Default: true
                    },
                    middleware?: string|list<string|array{ // Default: []
                        id?: scalar|Param|null,
                        arguments?: list<mixed>,
                    }>,
                }>,
            },
            scheduler?: bool|array{ // Scheduler configuration
                enabled?: bool|Param, // Default: false
            },
            disallow_search_engine_index?: bool|Param, // Enabled by default when debug is enabled. // Default: true
            http_client?: bool|array{ // HTTP Client configuration
                enabled?: bool|Param, // Default: true
                max_host_connections?: int|Param, // The maximum number of connections to a single host.
                default_options?: array{
                    headers?: array<string, mixed>,
                    vars?: array<string, mixed>,
                    max_redirects?: int|Param, // The maximum number of redirects to follow.
                    http_version?: scalar|Param|null, // The default HTTP version, typically 1.1 or 2.0, leave to null for the best version.
                    resolve?: array<string, scalar|Param|null>,
                    proxy?: scalar|Param|null, // The URL of the proxy to pass requests through or null for automatic detection.
                    no_proxy?: scalar|Param|null, // A comma separated list of hosts that do not require a proxy to be reached.
                    timeout?: float|Param, // The idle timeout, defaults to the "default_socket_timeout" ini parameter.
                    max_duration?: float|Param, // The maximum execution time for the request+response as a whole.
                    bindto?: scalar|Param|null, // A network interface name, IP address, a host name or a UNIX socket to bind to.
                    verify_peer?: bool|Param, // Indicates if the peer should be verified in a TLS context.
                    verify_host?: bool|Param, // Indicates if the host should exist as a certificate common name.
                    cafile?: scalar|Param|null, // A certificate authority file.
                    capath?: scalar|Param|null, // A directory that contains multiple certificate authority files.
                    local_cert?: scalar|Param|null, // A PEM formatted certificate file.
                    local_pk?: scalar|Param|null, // A private key file.
                    passphrase?: scalar|Param|null, // The passphrase used to encrypt the "local_pk" file.
                    ciphers?: scalar|Param|null, // A list of TLS ciphers separated by colons, commas or spaces (e.g. "RC3-SHA:TLS13-AES-128-GCM-SHA256"...)
                    peer_fingerprint?: array{ // Associative array: hashing algorithm => hash(es).
                        sha1?: mixed,
                        pin-sha256?: mixed,
                        md5?: mixed,
                    },
                    crypto_method?: scalar|Param|null, // The minimum version of TLS to accept; must be one of STREAM_CRYPTO_METHOD_TLSv*_CLIENT constants.
                    extra?: array<string, mixed>,
                    rate_limiter?: scalar|Param|null, // Rate limiter name to use for throttling requests. // Default: null
                    caching?: bool|array{ // Caching configuration.
                        enabled?: bool|Param, // Default: false
                        cache_pool?: string|Param, // The taggable cache pool to use for storing the responses. // Default: "cache.http_client"
                        shared?: bool|Param, // Indicates whether the cache is shared (public) or private. // Default: true
                        max_ttl?: int|Param, // The maximum TTL (in seconds) allowed for cached responses. // Default: 86400
                    },
                    retry_failed?: bool|array{
                        enabled?: bool|Param, // Default: false
                        retry_strategy?: scalar|Param|null, // service id to override the retry strategy. // Default: null
                        http_codes?: int|string|array<string, array{ // Default: []
                            code?: int|Param,
                            methods?: string|list<string|Param>,
                        }>,
                        max_retries?: int|Param, // Default: 3
                        delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
                        multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: delay(multiple ^ retries). // Default: 2
                        max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
                        jitter?: float|Param, // Randomness in percent (between 0 and 1) to apply to the delay. // Default: 0.1
                    },
                },
                mock_response_factory?: scalar|Param|null, // `true` to always return empty 200 responses, or the id of the service to use to generate mock responses - which should be either an invokable or an iterable.
                scoped_clients?: array<string, string|array{ // Default: []
                    scope?: scalar|Param|null, // The regular expression that the request URL must match before adding the other options. When none is provided, the base URI is used instead.
                    base_uri?: scalar|Param|null, // The URI to resolve relative URLs, following rules in RFC 3985, section 2.
                    auth_basic?: scalar|Param|null, // An HTTP Basic authentication "username:password".
                    auth_bearer?: scalar|Param|null, // A token enabling HTTP Bearer authorization.
                    auth_ntlm?: scalar|Param|null, // A "username:password" pair to use Microsoft NTLM authentication (requires the cURL extension).
                    query?: array<string, scalar|Param|null>,
                    headers?: array<string, mixed>,
                    max_redirects?: int|Param, // The maximum number of redirects to follow.
                    http_version?: scalar|Param|null, // The default HTTP version, typically 1.1 or 2.0, leave to null for the best version.
                    resolve?: array<string, scalar|Param|null>,
                    proxy?: scalar|Param|null, // The URL of the proxy to pass requests through or null for automatic detection.
                    no_proxy?: scalar|Param|null, // A comma separated list of hosts that do not require a proxy to be reached.
                    timeout?: float|Param, // The idle timeout, defaults to the "default_socket_timeout" ini parameter.
                    max_duration?: float|Param, // The maximum execution time for the request+response as a whole.
                    bindto?: scalar|Param|null, // A network interface name, IP address, a host name or a UNIX socket to bind to.
                    verify_peer?: bool|Param, // Indicates if the peer should be verified in a TLS context.
                    verify_host?: bool|Param, // Indicates if the host should exist as a certificate common name.
                    cafile?: scalar|Param|null, // A certificate authority file.
                    capath?: scalar|Param|null, // A directory that contains multiple certificate authority files.
                    local_cert?: scalar|Param|null, // A PEM formatted certificate file.
                    local_pk?: scalar|Param|null, // A private key file.
                    passphrase?: scalar|Param|null, // The passphrase used to encrypt the "local_pk" file.
                    ciphers?: scalar|Param|null, // A list of TLS ciphers separated by colons, commas or spaces (e.g. "RC3-SHA:TLS13-AES-128-GCM-SHA256"...).
                    peer_fingerprint?: array{ // Associative array: hashing algorithm => hash(es).
                        sha1?: mixed,
                        pin-sha256?: mixed,
                        md5?: mixed,
                    },
                    crypto_method?: scalar|Param|null, // The minimum version of TLS to accept; must be one of STREAM_CRYPTO_METHOD_TLSv*_CLIENT constants.
                    mock_response_factory?: scalar|Param|null, // `true` to always return empty 200 responses, `false` to disable mocking, or the id of the service to use to generate mock responses (invokable or iterable).
                    extra?: array<string, mixed>,
                    rate_limiter?: scalar|Param|null, // Rate limiter name to use for throttling requests. // Default: null
                    caching?: bool|array{ // Caching configuration.
                        enabled?: bool|Param, // Default: false
                        cache_pool?: string|Param, // The taggable cache pool to use for storing the responses. // Default: "cache.http_client"
                        shared?: bool|Param, // Indicates whether the cache is shared (public) or private. // Default: true
                        max_ttl?: int|Param, // The maximum TTL (in seconds) allowed for cached responses. // Default: 86400
                    },
                    retry_failed?: bool|array{
                        enabled?: bool|Param, // Default: false
                        retry_strategy?: scalar|Param|null, // service id to override the retry strategy. // Default: null
                        http_codes?: int|string|array<string, array{ // Default: []
                            code?: int|Param,
                            methods?: string|list<string|Param>,
                        }>,
                        max_retries?: int|Param, // Default: 3
                        delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
                        multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: delay(multiple ^ retries). // Default: 2
                        max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
                        jitter?: float|Param, // Randomness in percent (between 0 and 1) to apply to the delay. // Default: 0.1
                    },
                }>,
            },
            mailer?: bool|array{ // Mailer configuration
                enabled?: bool|Param, // Default: false
                message_bus?: scalar|Param|null, // The message bus to use. Defaults to the default bus if the Messenger component is installed. // Default: null
                dsn?: scalar|Param|null, // Default: null
                transports?: array<string, scalar|Param|null>,
                envelope?: array{ // Mailer Envelope configuration
                    sender?: scalar|Param|null,
                    recipients?: string|list<scalar|Param|null>,
                    allowed_recipients?: string|list<scalar|Param|null>,
                },
                headers?: array<string, string|array{ // Default: []
                    value?: mixed,
                }>,
                dkim_signer?: bool|array{ // DKIM signer configuration
                    enabled?: bool|Param, // Default: false
                    key?: scalar|Param|null, // Key content, or path to key (in PEM format with the `file://` prefix) // Default: ""
                    domain?: scalar|Param|null, // Default: ""
                    select?: scalar|Param|null, // Default: ""
                    passphrase?: scalar|Param|null, // The private key passphrase // Default: ""
                    options?: array<string, mixed>,
                },
                smime_signer?: bool|array{ // S/MIME signer configuration
                    enabled?: bool|Param, // Default: false
                    key?: scalar|Param|null, // Path to key (in PEM format) // Default: ""
                    certificate?: scalar|Param|null, // Path to certificate (in PEM format without the `file://` prefix) // Default: ""
                    passphrase?: scalar|Param|null, // The private key passphrase // Default: null
                    extra_certificates?: scalar|Param|null, // Default: null
                    sign_options?: int|Param, // Default: null
                },
                smime_encrypter?: bool|array{ // S/MIME encrypter configuration
                    enabled?: bool|Param, // Default: false
                    repository?: scalar|Param|null, // S/MIME certificate repository service. This service shall implement the `Symfony\Component\Mailer\EventListener\SmimeCertificateRepositoryInterface`. // Default: ""
                    cipher?: int|Param, // A set of algorithms used to encrypt the message // Default: null
                },
            },
            secrets?: bool|array{
                enabled?: bool|Param, // Default: true
                vault_directory?: scalar|Param|null, // Default: "%kernel.project_dir%/config/secrets/%kernel.runtime_environment%"
                local_dotenv_file?: scalar|Param|null, // Default: "%kernel.project_dir%/.env.%kernel.environment%.local"
                decryption_env_var?: scalar|Param|null, // Default: "base64:default::SYMFONY_DECRYPTION_SECRET"
            },
            notifier?: bool|array{ // Notifier configuration
                enabled?: bool|Param, // Default: false
                message_bus?: scalar|Param|null, // The message bus to use. Defaults to the default bus if the Messenger component is installed. // Default: null
                chatter_transports?: array<string, scalar|Param|null>,
                texter_transports?: array<string, scalar|Param|null>,
                notification_on_failed_messages?: bool|Param, // Default: false
                channel_policy?: array<string, string|list<scalar|Param|null>>,
                admin_recipients?: list<array{ // Default: []
                    email?: scalar|Param|null,
                    phone?: scalar|Param|null, // Default: ""
                }>,
            },
            rate_limiter?: bool|array{ // Rate limiter configuration
                enabled?: bool|Param, // Default: false
                limiters?: array<string, array{ // Default: []
                    lock_factory?: scalar|Param|null, // The service ID of the lock factory used by this limiter (or null to disable locking). // Default: "auto"
                    cache_pool?: scalar|Param|null, // The cache pool to use for storing the current limiter state. // Default: "cache.rate_limiter"
                    storage_service?: scalar|Param|null, // The service ID of a custom storage implementation, this precedes any configured "cache_pool". // Default: null
                    policy?: "fixed_window"|"token_bucket"|"sliding_window"|"compound"|"no_limit"|Param, // The algorithm to be used by this limiter.
                    limiters?: string|list<scalar|Param|null>,
                    limit?: int|Param, // The maximum allowed hits in a fixed interval or burst.
                    interval?: scalar|Param|null, // Configures the fixed interval if "policy" is set to "fixed_window" or "sliding_window". The value must be a number followed by "second", "minute", "hour", "day", "week" or "month" (or their plural equivalent).
                    rate?: array{ // Configures the fill rate if "policy" is set to "token_bucket".
                        interval?: scalar|Param|null, // Configures the rate interval. The value must be a number followed by "second", "minute", "hour", "day", "week" or "month" (or their plural equivalent).
                        amount?: int|Param, // Amount of tokens to add each interval. // Default: 1
                    },
                    anchor_at?: scalar|Param|null, // Aligns the "fixed_window" policy to a calendar (e.g. "2024-01-05 00:00:00 UTC" combined with `interval: 1 month` resets the counter on the 5th of each month). UTC if not specified. // Default: null
                }>,
            },
            uid?: bool|array{ // Uid configuration
                enabled?: bool|Param, // Default: false
                default_uuid_version?: 7|6|4|1|Param, // Default: 7
                name_based_uuid_version?: 5|3|Param, // Default: 5
                name_based_uuid_namespace?: scalar|Param|null,
                time_based_uuid_version?: 7|6|1|Param, // Default: 7
                time_based_uuid_node?: scalar|Param|null,
                uuid47_secret?: scalar|Param|null, // A high-entropy secret used by the "uuid47_transformer" service. Defaults to "kernel.secret". // Default: null
            },
            html_sanitizer?: bool|array{ // HtmlSanitizer configuration
                enabled?: bool|Param, // Default: false
                sanitizers?: array<string, array{ // Default: []
                    default_action?: "drop"|"block"|"allow"|Param, // Defines how the sanitizer must behave by default.
                    allow_safe_elements?: bool|Param, // Allows "safe" elements and attributes. // Default: false
                    allow_static_elements?: bool|Param, // Allows all static elements and attributes from the W3C Sanitizer API standard. // Default: false
                    allow_elements?: array<string, mixed>,
                    block_elements?: string|list<string|Param>,
                    drop_elements?: string|list<string|Param>,
                    allow_attributes?: array<string, mixed>,
                    drop_attributes?: array<string, mixed>,
                    force_attributes?: array<string, array<string, string|Param>>,
                    force_https_urls?: bool|Param, // Transforms URLs using the HTTP scheme to use the HTTPS scheme instead. // Default: false
                    allowed_link_schemes?: string|list<string|Param>,
                    allowed_link_hosts?: null|string|list<string|Param>,
                    allow_relative_links?: bool|Param, // Allows relative URLs to be used in links href attributes. // Default: false
                    allowed_media_schemes?: string|list<string|Param>,
                    allowed_media_hosts?: null|string|list<string|Param>,
                    allow_relative_medias?: bool|Param, // Allows relative URLs to be used in media source attributes (img, audio, video, ...). // Default: false
                    with_attribute_sanitizers?: string|list<string|Param>,
                    without_attribute_sanitizers?: string|list<string|Param>,
                    max_input_length?: int|Param, // The maximum length allowed for the sanitized input. // Default: 0
                }>,
            },
            webhook?: bool|array{ // Webhook configuration
                enabled?: bool|Param, // Default: false
                message_bus?: scalar|Param|null, // The message bus to use. // Default: "messenger.default_bus"
                event_header_name?: scalar|Param|null, // Default: "Webhook-Event"
                id_header_name?: scalar|Param|null, // Default: "Webhook-Id"
                signature_header_name?: scalar|Param|null, // Default: "Webhook-Signature"
                signing_algorithm?: scalar|Param|null, // Default: "sha256"
                routing?: array<string, array{ // Default: []
                    service?: scalar|Param|null,
                    secret?: scalar|Param|null, // Default: ""
                }>,
            },
            remote-event?: bool|array{ // RemoteEvent configuration
                enabled?: bool|Param, // Default: false
            },
            json_streamer?: bool|array{ // JSON streamer configuration
                enabled?: bool|Param, // Default: false
                default_options?: array{
                    include_null_properties?: bool|Param, // Encode the properties with null value // Default: false
                    ...<string, mixed>
                },
            },
        }
        PHP;

    private Parser $phplrt;

    public function prepare(): void
    {
        $this->phplrt = new \Phplrt\Parser\Parser(
            lexer: $lexer = new readonly class extends Lexer {
                public const int T_DQ_STRING_LITERAL = 0;
                public const int T_SQ_STRING_LITERAL = 1;
                public const int T_PFX_FLOAT_LITERAL = 2;
                public const int T_SFX_FLOAT_LITERAL = 3;
                public const int T_EXP_LITERAL = 4;
                public const int T_BIN_INT_LITERAL = 5;
                public const int T_OCT_INT_LITERAL = 6;
                public const int T_HEX_INT_LITERAL = 7;
                public const int T_DEC_INT_LITERAL = 8;
                public const int T_BOOL_LITERAL = 9;
                public const int T_NULL_LITERAL = 10;
                public const int T_NEQ = 11;
                public const int T_EQ = 12;
                public const int T_THIS = 13;
                public const int T_VARIABLE = 14;
                public const int T_NAME_WITH_SPACE = 15;
                public const int T_NAME = 16;
                public const int T_LTE = 17;
                public const int T_GTE = 18;
                public const int T_ANGLE_BRACKET_OPEN = 19;
                public const int T_ANGLE_BRACKET_CLOSE = 20;
                public const int T_PARENTHESIS_OPEN = 21;
                public const int T_PARENTHESIS_CLOSE = 22;
                public const int T_BRACE_OPEN = 23;
                public const int T_BRACE_CLOSE = 24;
                public const int T_ATTR_OPEN = 25;
                public const int T_SQUARE_BRACKET_OPEN = 26;
                public const int T_SQUARE_BRACKET_CLOSE = 27;
                public const int T_COMMA = 28;
                public const int T_ELLIPSIS = 29;
                public const int T_DOUBLE_COLON = 30;
                public const int T_COLON = 31;
                public const int T_ASSIGN = 32;
                public const int T_NS_DELIMITER = 33;
                public const int T_QMARK = 34;
                public const int T_OR = 35;
                public const int T_AMP = 36;
                public const int T_ASTERISK = 37;
                public const int T_COMMENT = 38;
                public const int T_DOC_COMMENT = 39;
                public const int T_WHITESPACE = 40;

                public function __construct()
                {
                    parent::__construct(
                        pattern: '/\\G(?|(?:(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)")(*MARK:0))|(?:(?:\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')(*MARK:1))|(?:(?:\\-?[0-9]++\\.[0-9]*+(?:[eE]-?[0-9]++)?)(*MARK:2))|(?:(?:\\-?[0-9]*+\\.[0-9]++(?:[eE]-?[0-9]++)?)(*MARK:3))|(?:(?:\\-?[0-9]++[eE]-?[0-9]++)(*MARK:4))|(?:(?:\\-?0[bB][01_]++)(*MARK:5))|(?:(?:\\-?0[oO][0-7_]++)(*MARK:6))|(?:(?:\\-?0[xX][0-9a-fA-F_]++)(*MARK:7))|(?:(?:\\-?[0-9][0-9_]*+)(*MARK:8))|(?:(?:(?i)(?:true|false)(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:9))|(?:(?:(?i)null(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:10))|(?:(?:(?i)is\\h++not(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:11))|(?:(?:(?i)is(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:12))|(?:(?:\\$this\\b)(*MARK:13))|(?:(?:\\$[a-zA-Z_\\x80-\\xff][a-zA-Z0-9\\-_\\x80-\\xff]*+)(*MARK:14))|(?:(?:[a-zA-Z_\\x80-\\xff][a-zA-Z0-9\\-_\\x80-\\xff]*+\\s++)(*MARK:15))|(?:(?:[a-zA-Z_\\x80-\\xff][a-zA-Z0-9\\-_\\x80-\\xff]*+)(*MARK:16))|(?:(?:<=)(*MARK:17))|(?:(?:>=)(*MARK:18))|(?:(?:<)(*MARK:19))|(?:(?:>)(*MARK:20))|(?:(?:\\()(*MARK:21))|(?:(?:\\))(*MARK:22))|(?:(?:\\{)(*MARK:23))|(?:(?:\\})(*MARK:24))|(?:(?:\\#\\[)(*MARK:25))|(?:(?:\\[)(*MARK:26))|(?:(?:\\])(*MARK:27))|(?:(?:,)(*MARK:28))|(?:(?:\\.\\.\\.)(*MARK:29))|(?:(?:::)(*MARK:30))|(?:(?::)(*MARK:31))|(?:(?:=)(*MARK:32))|(?:(?:\\\\)(*MARK:33))|(?:(?:\\?)(*MARK:34))|(?:(?:\\|)(*MARK:35))|(?:(?:&)(*MARK:36))|(?:(?:\\*)(*MARK:37))|(?:(?:(?:\\/\\/|\\#)[^\\r\\n]*+)(*MARK:38))|(?:(?:\\/\\*.*?\\*\\/)(*MARK:39))|(?:(?:\\s++)(*MARK:40))|(?:(?:[^\\s]++)(*MARK:41)))/Ssum',
                        channels: [
                            38 => 'hidden',
                            'hidden',
                            'hidden',
                            'unknown',
                        ],
                        names: [
                            'T_DQ_STRING_LITERAL',
                            'T_SQ_STRING_LITERAL',
                            'T_PFX_FLOAT_LITERAL',
                            'T_SFX_FLOAT_LITERAL',
                            'T_EXP_LITERAL',
                            'T_BIN_INT_LITERAL',
                            'T_OCT_INT_LITERAL',
                            'T_HEX_INT_LITERAL',
                            'T_DEC_INT_LITERAL',
                            'T_BOOL_LITERAL',
                            'T_NULL_LITERAL',
                            'T_NEQ',
                            'T_EQ',
                            'T_THIS',
                            'T_VARIABLE',
                            'T_NAME_WITH_SPACE',
                            'T_NAME',
                            'T_LTE',
                            'T_GTE',
                            'T_ANGLE_BRACKET_OPEN',
                            'T_ANGLE_BRACKET_CLOSE',
                            'T_PARENTHESIS_OPEN',
                            'T_PARENTHESIS_CLOSE',
                            'T_BRACE_OPEN',
                            'T_BRACE_CLOSE',
                            'T_ATTR_OPEN',
                            'T_SQUARE_BRACKET_OPEN',
                            'T_SQUARE_BRACKET_CLOSE',
                            'T_COMMA',
                            'T_ELLIPSIS',
                            'T_DOUBLE_COLON',
                            'T_COLON',
                            'T_ASSIGN',
                            'T_NS_DELIMITER',
                            'T_QMARK',
                            'T_OR',
                            'T_AMP',
                            'T_ASTERISK',
                            'T_COMMENT',
                            'T_DOC_COMMENT',
                            'T_WHITESPACE',
                        ],
                    );
                }
            },
            grammar: [
                new \Phplrt\Parser\Grammar\Concatenation([6, 3, 7]),
                new \Phplrt\Parser\Grammar\Concatenation([3, 10]),
                new \Phplrt\Parser\Grammar\Alternation([0, 1]),
                new \Phplrt\Parser\Grammar\Alternation([11, 12, 13, 14, 15]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NS_DELIMITER, false),
                new \Phplrt\Parser\Grammar\Concatenation([4, 3]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NS_DELIMITER, false),
                new \Phplrt\Parser\Grammar\Repetition(5, 0, INF),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NS_DELIMITER, false),
                new \Phplrt\Parser\Grammar\Concatenation([8, 3]),
                new \Phplrt\Parser\Grammar\Repetition(9, 0, INF),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NAME, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NAME_WITH_SPACE, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_EQ, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_BOOL_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NULL_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NAME_WITH_SPACE, true),
                new \Phplrt\Parser\Grammar\Alternation([21, 22, 23, 24, 25]),
                new \Phplrt\Parser\Grammar\Concatenation([2, 39]),
                new \Phplrt\Parser\Grammar\Concatenation([2, 43, 44]),
                new \Phplrt\Parser\Grammar\Alternation([17, 18, 19]),
                new \Phplrt\Parser\Grammar\Alternation([30, 31]),
                new \Phplrt\Parser\Grammar\Alternation([32, 33, 34]),
                new \Phplrt\Parser\Grammar\Alternation([35, 36, 37, 38]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_BOOL_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NULL_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_VARIABLE, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_THIS, true),
                new \Phplrt\Parser\Grammar\Alternation([26, 27]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_THIS, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_DQ_STRING_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_SQ_STRING_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PFX_FLOAT_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_SFX_FLOAT_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_EXP_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_BIN_INT_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_OCT_INT_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_HEX_INT_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_DEC_INT_LITERAL, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ASTERISK, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ASTERISK, true),
                new \Phplrt\Parser\Grammar\Concatenation([3, 40]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ASTERISK, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_DOUBLE_COLON, false),
                new \Phplrt\Parser\Grammar\Alternation([41, 3, 42]),
                new \Phplrt\Parser\Grammar\Concatenation([57, 58]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Concatenation([46, 45]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ANGLE_BRACKET_OPEN, false),
                new \Phplrt\Parser\Grammar\Repetition(47, 0, INF),
                new \Phplrt\Parser\Grammar\Optional(48),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ANGLE_BRACKET_CLOSE, false),
                new \Phplrt\Parser\Grammar\Concatenation([49, 45, 50, 51, 52]),
                new \Phplrt\Parser\Grammar\Repetition(155, 1, INF),
                new \Phplrt\Parser\Grammar\Concatenation([16, 59]),
                new \Phplrt\Parser\Grammar\Concatenation([59]),
                new \Phplrt\Parser\Grammar\Optional(54),
                new \Phplrt\Parser\Grammar\Alternation([55, 56]),
                new \Phplrt\Parser\Grammar\Concatenation([175]),
                new \Phplrt\Parser\Grammar\Concatenation([67, 71, 72]),
                new \Phplrt\Parser\Grammar\Concatenation([107, 59]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PARENTHESIS_OPEN, false),
                new \Phplrt\Parser\Grammar\Optional(60),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PARENTHESIS_CLOSE, false),
                new \Phplrt\Parser\Grammar\Optional(61),
                new \Phplrt\Parser\Grammar\Concatenation([2, 62, 63, 64, 65]),
                new \Phplrt\Parser\Grammar\Concatenation([74, 73]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Concatenation([68, 67]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Repetition(69, 0, INF),
                new \Phplrt\Parser\Grammar\Optional(70),
                new \Phplrt\Parser\Grammar\Concatenation([78, 79]),
                new \Phplrt\Parser\Grammar\Optional(54),
                new \Phplrt\Parser\Grammar\Concatenation([93, 92]),
                new \Phplrt\Parser\Grammar\Alternation([83, 86, 88, 90, 80]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ASSIGN, true),
                new \Phplrt\Parser\Grammar\Alternation([75, 76]),
                new \Phplrt\Parser\Grammar\Optional(77),
                new \Phplrt\Parser\Grammar\Concatenation([28]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_AMP, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Concatenation([81, 82, 80]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_AMP, true),
                new \Phplrt\Parser\Grammar\Concatenation([84, 85, 80]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Concatenation([87, 80]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_AMP, true),
                new \Phplrt\Parser\Grammar\Concatenation([89, 80]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Concatenation([94, 95]),
                new \Phplrt\Parser\Grammar\Optional(91),
                new \Phplrt\Parser\Grammar\Concatenation([96, 106]),
                new \Phplrt\Parser\Grammar\Optional(28),
                new \Phplrt\Parser\Grammar\Concatenation([59]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_AMP, true),
                new \Phplrt\Parser\Grammar\Optional(97),
                new \Phplrt\Parser\Grammar\Concatenation([98, 99]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_AMP, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Optional(101),
                new \Phplrt\Parser\Grammar\Concatenation([102, 103]),
                new \Phplrt\Parser\Grammar\Alternation([100, 104]),
                new \Phplrt\Parser\Grammar\Optional(105),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COLON, false),
                new \Phplrt\Parser\Grammar\Concatenation([123, 126]),
                new \Phplrt\Parser\Grammar\Concatenation([121, 122]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Concatenation([110, 109]),
                new \Phplrt\Parser\Grammar\Optional(111),
                new \Phplrt\Parser\Grammar\Concatenation([108, 112]),
                new \Phplrt\Parser\Grammar\Optional(109),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_BRACE_OPEN, false),
                new \Phplrt\Parser\Grammar\Alternation([113, 114]),
                new \Phplrt\Parser\Grammar\Optional(115),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_BRACE_CLOSE, false),
                new \Phplrt\Parser\Grammar\Concatenation([116, 117, 118, 119]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ELLIPSIS, true),
                new \Phplrt\Parser\Grammar\Optional(53),
                new \Phplrt\Parser\Grammar\Concatenation([129, 130]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Concatenation([124, 123]),
                new \Phplrt\Parser\Grammar\Repetition(125, 0, INF),
                new \Phplrt\Parser\Grammar\Concatenation([131, 134, 135, 133]),
                new \Phplrt\Parser\Grammar\Concatenation([133]),
                new \Phplrt\Parser\Grammar\Optional(54),
                new \Phplrt\Parser\Grammar\Alternation([127, 128]),
                new \Phplrt\Parser\Grammar\Alternation([18, 19, 3, 23, 21]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_QMARK, true),
                new \Phplrt\Parser\Grammar\Concatenation([59]),
                new \Phplrt\Parser\Grammar\Optional(132),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COLON, false),
                new \Phplrt\Parser\Grammar\Alternation([53, 120]),
                new \Phplrt\Parser\Grammar\Optional(136),
                new \Phplrt\Parser\Grammar\Concatenation([2, 137]),
                new \Phplrt\Parser\Grammar\Concatenation([176]),
                new \Phplrt\Parser\Grammar\Optional(142),
                new \Phplrt\Parser\Grammar\Concatenation([139, 140]),
                new \Phplrt\Parser\Grammar\Concatenation([145, 146, 147, 59, 148, 59]),
                new \Phplrt\Parser\Grammar\Concatenation([28, 142]),
                new \Phplrt\Parser\Grammar\Alternation([141, 143]),
                new \Phplrt\Parser\Grammar\Alternation([149, 150, 151, 152, 153, 154]),
                new \Phplrt\Parser\Grammar\Alternation([59, 28]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_QMARK, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COLON, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_EQ, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_NEQ, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_GTE, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_LTE, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ANGLE_BRACKET_OPEN, true),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ANGLE_BRACKET_CLOSE, true),
                new \Phplrt\Parser\Grammar\Concatenation([158, 156, 159, 160]),
                new \Phplrt\Parser\Grammar\Concatenation([161, 164]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_ATTR_OPEN, false),
                new \Phplrt\Parser\Grammar\Optional(157),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_SQUARE_BRACKET_CLOSE, false),
                new \Phplrt\Parser\Grammar\Concatenation([2, 166]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Concatenation([162, 161]),
                new \Phplrt\Parser\Grammar\Repetition(163, 0, INF),
                new \Phplrt\Parser\Grammar\Concatenation([171, 167, 172, 173, 174]),
                new \Phplrt\Parser\Grammar\Optional(165),
                new \Phplrt\Parser\Grammar\Concatenation([59]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Concatenation([168, 167]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_COMMA, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PARENTHESIS_OPEN, false),
                new \Phplrt\Parser\Grammar\Repetition(169, 0, INF),
                new \Phplrt\Parser\Grammar\Optional(170),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PARENTHESIS_CLOSE, false),
                new \Phplrt\Parser\Grammar\Concatenation([144]),
                new \Phplrt\Parser\Grammar\Concatenation([177, 180]),
                new \Phplrt\Parser\Grammar\Concatenation([181, 184]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_OR, false),
                new \Phplrt\Parser\Grammar\Concatenation([178, 176]),
                new \Phplrt\Parser\Grammar\Optional(179),
                new \Phplrt\Parser\Grammar\Concatenation([185]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_AMP, false),
                new \Phplrt\Parser\Grammar\Concatenation([182, 177]),
                new \Phplrt\Parser\Grammar\Optional(183),
                new \Phplrt\Parser\Grammar\Alternation([188, 186]),
                new \Phplrt\Parser\Grammar\Concatenation([189, 191]),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_QMARK, true),
                new \Phplrt\Parser\Grammar\Concatenation([187, 186]),
                new \Phplrt\Parser\Grammar\Alternation([197, 29, 20, 66, 138]),
                new \Phplrt\Parser\Grammar\Concatenation([192, 193, 194]),
                new \Phplrt\Parser\Grammar\Repetition(190, 0, INF),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_SQUARE_BRACKET_OPEN, true),
                new \Phplrt\Parser\Grammar\Optional(59),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_SQUARE_BRACKET_CLOSE, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PARENTHESIS_OPEN, false),
                new \Phplrt\Parser\Grammar\Lexeme($lexer::T_PARENTHESIS_CLOSE, false),
                new \Phplrt\Parser\Grammar\Concatenation([195, 59, 196]),
            ],
            initial: 59,
            reducers: [],
        );
    }

    public function benchParserTracing(): void
    {
        $this->phplrt->check(self::SAMPLE);
    }
}

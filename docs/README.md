# Core

The core module provides the entry point for platform code. It includes functionality for retrieving information about the current environment, the module registry, plus parsing and reading the project configuration.

**Note** all functions documented below are under the `HM\Platform` namespace.

## Environment

The following functions are available to use at any point on or after the `hm-platform.autoloader_loaded` action.

**`get_environment_name() : string`**

Returns the current hosting stack name based on the `HM_ENV` constant. On local setups this will return `unknown`.

**`get_environment_type() : string`**

Returns one of `local`, `development`, `staging` or `production`. This is read from the `HM_ENV_TYPE` constant except on local setups.

**`get_environment_architecture() : string`**

Returns the current server architecture, currently this is one of `ecs` or `ec2`. `ec2` is the legacy architecture while `ecs` is the new container based system.

## AWS SDK

The AWS SDK is always available and preconfigured with the necessary credentials on all non local servers. Access to additional APIs can be requested if needed.

**`get_aws_sdk() : Aws\Sdk`**

Returns an instance of the base AWS SDK with preconfigured credentials.

The credentials can be supplied locally by defining the constants `HM_ENV_REGION`, `AWS_KEY` and `AWS_SECRET`.

## Configuration

The configuration for the project determines which modules are loaded and how they behave. It can also be used for arbitrary project configuration.

### Functions

**`get_config() : array`**

Returns the complete configuration for the project including modules and their defaults and any overrides from the root `composer.json` configuration.

### Filters

These filters are intended for use by autoloaded files in modules and must be hooked into early. They provide a means of adding additional configuration features such as per environment overrides and configuration post-processing.

**`hm-platform.config.default : array $default_config`**

Filters the default base config to merge defaults and overrides into.

**`hm-platform.config : array $config`**

Filters the final config returned by `get_config()`.

## Modules

Note that the modules interface is intended for internal use only and is documented here for completeness.

### Functions

**`register_module( string $slug, string $directory, string $title, ?array $default_settings, ?callable $loader ) : Module`**

Registers and returns a `Module` object. If the module setting `enabled` is true the loader callback will be run on the `hm-platform.modules.<slug>.loaded` action hook.

This function must be called on the `hm-platform.modules.init` action hook.

**`get_enabled_modules() : array`**

Returns an array of `Module` objects with their `enabled` setting set to `true`.

### Actions

**`hm-platform.modules.init`**

Fired after the autoloader has been included. Modules can only be registered on this hook.

**`hm-platform.modules.<slug>.loaded : Module $module`**

Used to fire a module's registered loader callback. Recieves the `Module` object registered with the corresponding slug as an argument.

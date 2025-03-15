# Changelog

All notable changes to `laravel-wave` will be documented in this file.

## [Unreleased](https://github.com/qruto/laravel-wave/compare/0.10.0...main)

## [0.10.0](https://github.com/qruto/laravel-wave/compare/0.9.1...0.10.0) - 2025-03-15

- Laravel 12 support
- drop PHP 8.1 support

## [0.9.1](https://github.com/qruto/laravel-wave/compare/0.9.0...0.9.1) - 2024-04-13

- Fixed channels route file copy #63

## [0.9.0](https://github.com/qruto/laravel-wave/compare/0.8.2...0.9.0) - 2024-03-25

- Laravel 11 and `php artisan install:broadcasting` command support
- increase max. available connections prompt for `php artisan serve` command

## [0.8.2](https://github.com/qruto/laravel-wave/compare/0.8.1...0.8.2) - 2024-03-12

- Fixed working with `predis/predis`

## [0.8.1](https://github.com/qruto/laravel-wave/compare/0.8.0...0.8.1) - 2023-12-15

Fixed route caching

## [0.8.0](https://github.com/qruto/laravel-wave/compare/0.7.1...0.8.0) - 2023-12-14

- **Compatibility Testing with Laravel Octane**: Conducted thorough testing with [Laravel Octane](https://laravel.com/docs/10.x/octane), ensuring seamless integration and robust performance under the high-throughput, long-lived application scenarios that Octane facilitates.
- **Enhanced Efficiency for Presence Channels**: Re-engineered the data structure, significantly boosting the efficiency of data storage and retrieval processes for presence channels.
- **Migration to Redis Streams**: Transitioned the event history storage mechanism to [Redis Streams](https://redis.io/docs/data-types/streams). This change leverages Redis's advanced capabilities for more robust and scalable event stream resume.
- **Atomic Operations with Lua Scripts**: Integrated Lua scripts for atomic operations in Redis. This enhancement not only accelerates performance but also fortifies data integrity and effectively addresses potential race conditions.
- **Streamlined Naming Conventions**: Simplified the namespace and service provider's name for greater ease of use and clarity. The service provider has been renamed from `LaravelWave` to `Wave`.

## [0.7.1](https://github.com/qruto/laravel-wave/compare/0.7.0...0.7.1) - 2023-07-20

Various bug fixes.

Improved presence channel users synchronization.

## [0.7.0](https://github.com/qruto/laravel-wave/compare/0.6.1...0.7.0) - 2023-06-09

### 🎛️ Take a full control.

Migrated from the [legacy `EventSource`](https://github.com/whatwg/html/issues/2177#issuecomment-267270198) to state-of-the-art  [@microsoft/fetch-event-source](https://github.com/Azure/fetch-event-source) based on [`fetch`](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API) 💪.

### What's new

- **Support for Custom Authentication Headers**: As of [Echo 1.14.0](https://github.com/laravel/echo/releases/tag/v1.14.0), you can personalize your auth headers. Thanks to @ezequidias for the inspiration in https://github.com/qruto/laravel-wave/discussions/20
- **Debug Mode**: Idea from https://github.com/qruto/laravel-wave-js/discussions/14
- **[`retry`](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events#retry) Field Support**: We've added support for the `retry` field for setup reconnection time after connection close.
- **Intelligent Connection Management with `pauseInactive`:** This feature taps into the [Page Visibility API](https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API) to close connections when the document is hidden (like when a user minimizes the window), and auto-retries with the last event ID when it becomes visible again. This optimizes your server load.
- **Custom CSRF Token Support**: Craft your CSRF tokens as you see fit.
- **Full Customizability for Request Options**: You now have the power to tailor any [Request option](https://developer.mozilla.org/en-US/docs/Web/API/Request/Request#options) to your needs.

Check out all [Available Options → ⚙️](https://github.com/qruto/laravel-wave#client-options)

#### Fixed

- **Enhanced Error Handling**: Our `.error(...)` callbacks are now fully operational.
- **Persistent Leave Presence Channel Request**: With the new `keepalive` option, your leave presence channel requests will be sent even if a user closes their browser.

## [0.6.1](https://github.com/qruto/laravel-wave/compare/0.6.0...0.6.1) - 2023-04-27

Fixed route caching with double naming conflict https://github.com/qruto/laravel-wave/issues/15

## [0.6.0](https://github.com/qruto/laravel-wave/compare/0.5.2...0.6.0) - 2023-03-07

Laravel 10 support

## [0.5.2](https://github.com/qruto/laravel-wave/compare/0.5.1...0.5.2) - 2022-08-16

Required php version dropped to 8.0 ⬇️

## [0.5.1](https://github.com/qruto/laravel-wave/compare/0.5.0...0.5.1) - 2022-08-04

🤖 Automated ping events triggered by Wave connection requests.

## [0.5.0](https://github.com/qruto/laravel-wave/compare/3cacf22...0.5.0) - 2022-08-01

First release  🎉 Works well in the home environment, but should be battle tested before **1.0**.

Checkout ➡️ [README](https://github.com/qruto/laravel-wave/blob/main/README.md).

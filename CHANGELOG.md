# Release Notes

## 1.1.0

- Method `Readable::getStreamContents()` was deprecated.
- The stream package (PSR-7 based) was added.
- Method `Readable::getStream(): StreamInterface` was added.

## 1.0.2

- Fix `Exception::throwsIn` method (remove clone operation).

## 1.0.1

- Allow previous exception overriding using method `Exception::from($exception, $previous)`
- Fix `Exception::from` method (remove clone operation)

## 1.0.0

- Initial release

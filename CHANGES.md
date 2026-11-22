# Changes in 0.8.6 (2026-11-23)

- **Timer alarm**: the dive klaxon is louder again. It now goes through a compressor with make-up gain, which raised the average level by about 1.6 dB (measured with the browser's audio engine) while keeping the peaks below clipping. The Sound switch still silences it.
- No database changes (version number bump only).
- Tests: the Behat audio spy now understands the compressor and gain controls.

# Changes in 0.8.7 (2026-11-23)

- **Timer alarm**: the dive klaxon is louder again. Its two layers are now square waves instead of sawtooth waves, which raised the average level by about 2 dB (measured with the browser's audio engine) with the peak still well below clipping. The tone is a little buzzier. The compressor added in 0.8.6 is no longer needed and was removed. The Sound switch still silences it.
- No database changes (version number bump only).

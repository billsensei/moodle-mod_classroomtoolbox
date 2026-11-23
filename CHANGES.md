# Changes in 0.11.1 (2026-11-23)

- **Duck quack** (one of the timer's alarm sounds) was rebuilt to sound more like a real duck. Each quack now has a short "k" click, a buzzy voice whose pitch jumps up and then falls fast, two nasal frequency peaks (about 800 Hz and 2000 Hz), a fast growl in the volume and a little saturation for harshness. It plays seven quacks, "quack, quack, quack-quack-quack", each a little lower than the last, about 2.3 seconds in all. Measured with the browser's audio engine: average level -11 dB, peak 0.96 (no clipping), and the energy has the two-peak shape of a nasal quack. It was built from how quacks are described acoustically, not from a recording.
- The other five sounds are unchanged.
- No database changes (version number bump only).
- Tests: the audio spy used by the Behat tests understands the extra audio parts the quack needs.

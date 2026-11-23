# Changes in 0.11.2 (2026-11-23)

- **Duck quack** (one of the timer's alarm sounds) was rebuilt again, this time tuned against measurements of a real recorded quack (the macOS "Quack" sound, used only as a measuring example: no recording is included). The real quack turned out to be low and buzzy (about 145 Hz rising to 182 Hz and falling to 138 Hz over 0.17 s), with most of its energy between 1.4 and 2.0 kHz and almost none above 4 kHz, and no separate noise click. The new quack follows that: the same pitch contour and growl, three formant bands that match the measured spectrum within about 2 dB per band (except above 4 kHz), a similar volume shape, and soft saturation with a low-pass filter so it is as loud as the real clip (average -10.7 dB, peak 0.94). The alarm plays six quacks, each a little lower than the last, about 2.2 seconds in all.
- The other five sounds are unchanged.
- No database changes (version number bump only).
- Tests: the Behat check for the duck now expects six notes (one voice per quack).

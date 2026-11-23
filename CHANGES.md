# Changes in 0.12.1 (2026-11-23)

- **Both wheels can spin at the same time.** With two wheels, press one wheel's Spin button and then the other's while the first is still turning, and both spin together. The wheels stop at different moments; each winner dialog says which wheel it is from, and if both have stopped, the second dialog appears as soon as the first is closed, so no result is lost. Each result still offers **Remove from wheel** or **Keep on wheel**.
- While any wheel is spinning, the buttons that change the names (Add or Remove second wheel, Split names again, Reset wheel, Shuffle, Sort and the group tick boxes) wait until all the wheels have stopped.
- No database changes (version number bump only).
- Tests: a new scenario for spinning both wheels at once and a Behat step that presses both Spin buttons back to back; 52 PHPUnit tests.

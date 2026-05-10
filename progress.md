Original prompt: Bespoke debe ir siempre al centro en la previsualizacion no se va al centro, tambien quiero que crees un nivel totalmente fucnional y de maquina perpetua de moviemnto usando todas las piezas como demo

Progress:
- Reviewed current static Matter.js builder. No physical AGENTS.md or referenced ../.tessl/RULES.md exists in this repo path.
- Identified likely preview centering issue around canvas/layout resize when side panels are hidden.
- Added forced full-viewport preview canvas, resize observation, and preview offsets so the machine re-centers when panels disappear.
- Replaced the default scene with a built-in "Perpetual demo" level using pills, ramps, walls, dominoes, gears, elevator, spring, catapult, and trigger.
- Added deterministic `window.advanceTime` and `window.render_game_to_text` hooks for browser verification.
- Verified preview at 1280x720: `render_game_to_text` reports logo center (640,360) equal to canvas center (640,360), and screenshot `output/preview-check-2.png` shows the centered Bespoke logo with the demo moving.
- Verified preview at 390x844: logo center (195,422) equals canvas center (195,422).
- Changed Reset back to a blank scene and added a built-in `Blank` level alongside `Perpetual demo`.
- Added floating left/right panel toggles so edit panels can collapse and the canvas expands into corner space.
- Verified panel collapse in browser: canvas grows from 840x720 to 1030x720 with left panel collapsed and 1280x720 with both panels collapsed; no console errors.
- Removed demo pill auto-respawn: pills no longer reset to the start after waiting, idling, or crossing the previous demo bounds.
- Verified in Play mode across 900 advanced frames that demo pills keep their current positions instead of respawning at the start; no console errors.
- Strengthened catapult launch: larger magenta activation cup, higher launch speed range, higher demo pill speed cap, and demo catapult power raised to produce a visible parabolic arc. Verified in Play mode with pill speeds reaching 22 and no console errors.
- Made preview WYSIWYG with the editor: preview now reuses the editor stage size for logo layout and offsets the entire stage as one unit, without changing scale/relative coordinates. Catapult and elevator physics now start at the same x/y shown in edit mode. Verified immediate editor-vs-preview comparison has no part mismatches and logo delta is 0,0.
- Fixed edge editing regression: canvas now always spans the full stage under the panels, so collapsing/expanding panels does not change coordinates or hide edge pieces outside a resized canvas. Verified canvas stays 1280x720 with panels open/collapsed and a pill at x=1260,y=700 can be placed, selected, and deleted.

TODO:
- The original develop-web-game client could not run directly because its Playwright browser executable was missing; verification used the available `playwright-cli` session instead.

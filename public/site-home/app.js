(() => {
  const canvas = document.getElementById("motion-engine");
  const ctx = canvas.getContext("2d", { alpha: true });
  const palette = document.getElementById("palette");
  const inspector = document.getElementById("inspector");
  const selectedType = document.getElementById("selected-type");
  const levelListEl = document.getElementById("level-list");

  const builderEl = document.querySelector(".builder");
  const HOME_BASE = (window.BespokeHome?.base || "").replace(/\/$/, "");
  const assetUrl = (path) => {
    const normalized = path.replace(/^\.\//, "").replace(/^\//, "");
    return HOME_BASE ? `${HOME_BASE}/${normalized}` : `./${normalized}`;
  };

  const ui = {
    edit: document.getElementById("edit-toggle"),
    play: document.getElementById("play-toggle"),
    preview: document.getElementById("preview-toggle"),
    reset: document.getElementById("reset-scene"),
    saveNew: document.getElementById("save-new"),
    download: document.getElementById("download-level"),
    upload: document.getElementById("upload-level"),
    delete: document.getElementById("delete-part"),
    toggleLeftPanel: document.getElementById("toggle-left-panel"),
    toggleRightPanel: document.getElementById("toggle-right-panel"),
  };

  const IS_VIEWER = !palette;

  const ASSETS = {
    logoBase: assetUrl("logo_incompleto.svg"),
    logoFull: assetUrl("logo_completo.svg"),
    pill: assetUrl("pastilla.svg"),
  };

  // Curtain backdrop colors — contrast well with pink, yellow, black, white
  const CURTAIN_COLORS = [
    "rgba(100, 180, 220, 0.25)",   // sky blue
    "rgba(120, 200, 170, 0.25)",   // teal
    "rgba(160, 130, 200, 0.25)",   // lavender
    "rgba(200, 160, 100, 0.25)",   // warm sand
    "rgba(100, 160, 140, 0.25)",   // sage
    "rgba(180, 130, 160, 0.25)",   // mauve
    "rgba(130, 180, 130, 0.25)",   // soft green
    "rgba(170, 150, 200, 0.25)",   // periwinkle
    "rgba(255, 255, 255, 0.35)",   // white
  ];

  function sceneCurtainColors() {
    return state.scene.curtainColors || CURTAIN_COLORS;
  }

  function parseRgba(str) {
    const m = str.match(/rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
    if (!m) return { hex: "#888888", alpha: 0.25 };
    const r = Number(m[1]), g = Number(m[2]), b = Number(m[3]);
    const a = m[4] !== undefined ? Number(m[4]) : 1;
    return { hex: "#" + [r, g, b].map((v) => v.toString(16).padStart(2, "0")).join(""), alpha: a };
  }

  function toRgba(hex, alpha) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  function ensureSceneCurtainColors() {
    if (!state.scene.curtainColors) {
      state.scene.curtainColors = CURTAIN_COLORS.slice();
    }
    return state.scene.curtainColors;
  }

  // Shuffled queue ensures every color appears before any repeats
  let curtainQueue = [];
  function nextCurtainColor(lastColor) {
    if (curtainQueue.length === 0) {
      curtainQueue = sceneCurtainColors().slice();
      // Fisher-Yates shuffle
      for (let i = curtainQueue.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [curtainQueue[i], curtainQueue[j]] = [curtainQueue[j], curtainQueue[i]];
      }
      // If first in queue is same as last used, move it to the end
      if (curtainQueue[0] === lastColor && curtainQueue.length > 1) {
        curtainQueue.push(curtainQueue.shift());
      }
    }
    return curtainQueue.shift();
  }

  const LOGO = {
    width: 1148.34,
    height: 418.1,
    pillX: 675.61,
    pillY: 141.38,
    pillWidth: 156.52,
    pillHeight: 158.73,
  };

  const COLORS = {
    ink: "#1d1d1b",
    pink: "#da167a",
    yellow: "#fbb823",
    rail: "rgba(29, 29, 27, 0.38)",
    railSoft: "rgba(29, 29, 27, 0.16)",
    pinkRail: "rgba(218, 22, 122, 0.52)",
    yellowRail: "rgba(251, 184, 35, 0.62)",
    glass: "rgba(248, 249, 247, 0.72)",
  };

  const TOOLS = [
    { type: "select", label: "Select", icon: "↖" },
    { type: "pill", label: "Pill", icon: "●" },
    { type: "ramp", label: "Ramp", icon: "╱" },
    { type: "wall", label: "Wall", icon: "▌" },
    { type: "domino", label: "Domino", icon: "▯" },
    { type: "gear", label: "Gear", icon: "◎" },
    { type: "elevator", label: "Elevator", icon: "⇧" },
    { type: "spring", label: "Spring", icon: "⌁" },
    { type: "catapult", label: "Catapult", icon: "⤴" },
    { type: "trigger", label: "O Trigger", icon: "◌" },
    { type: "bucketLift", label: "Bucket Lift", icon: "⛓" },
  ];

  const SCHEMAS = {
    pill: ["x", "y", "r", "restitution", "density", "friction", "frictionAir", "inertia"],
    ramp: ["x", "y", "w", "h", "angle", "strokeColor", "fillColor"],
    wall: ["x", "y", "w", "h", "angle", "strokeColor", "fillColor"],
    domino: ["x", "y", "w", "h", "angle", "density", "strokeColor", "fillColor"],
    gear: ["x", "y", "r", "speed", "driveMode", "strokeColor"],
    elevator: ["x", "y", "w", "h", "travel", "speed", "mode", "startPos", "triggerAction", "strokeColor", "fillColor"],
    spring: ["x", "y", "w", "h", "angle", "power", "strokeColor"],
    catapult: ["x", "y", "w", "angle", "power", "flip", "strokeColor"],
    trigger: ["x", "y", "r", "power", "strokeColor"],
    bucketLift: ["x", "y", "w", "h", "speed", "bucketW", "buckets", "dropSide", "preloaded", "pillR", "pillBounce", "pillDensity", "pillFriction", "pillAir", "pillInertia", "showGuides", "strokeColor", "fillColor"],
  };

  const COLOR_FIELDS = new Set(["strokeColor", "fillColor"]);

  const LABELS = {
    x: "X",
    y: "Y",
    w: "Width",
    h: "Height",
    r: "Radius",
    angle: "Angle",
    teeth: "Teeth",
    speed: "Speed",
    power: "Power",
    travel: "Travel",
    density: "Weight",
    restitution: "Bounce",
    friction: "Grip",
    frictionAir: "Air Drag",
    inertia: "Spin Resist",
    mode: "Mode",
    startPos: "Start",
    triggerAction: "Action",
    driveMode: "Drive",
    bucketW: "Bucket W",
    buckets: "Buckets",
    preloaded: "Preloaded",
    flip: "Flip",
    pillR: "Pill Radius",
    pillBounce: "Pill Bounce",
    pillDensity: "Pill Weight",
    pillFriction: "Pill Grip",
    pillAir: "Pill Air Drag",
    pillInertia: "Pill Spin Res",
    strokeColor: "Stroke",
    fillColor: "Fill",
    dropSide: "Drop Side",
    showGuides: "Guides",
  };

  const SELECT_OPTIONS = {
    mode: [
      { value: "continuous", label: "Continuous" },
      { value: "trigger", label: "Trigger" },
    ],
    startPos: [
      { value: "top", label: "Top" },
      { value: "middle", label: "Middle" },
      { value: "bottom", label: "Bottom" },
    ],
    triggerAction: [
      { value: "permanent", label: "Permanent" },
      { value: "once", label: "Once" },
      { value: "cycle", label: "Cycle" },
    ],
    driveMode: [
      { value: "motor", label: "Motor" },
      { value: "free", label: "Free" },
      { value: "auto", label: "Auto" },
    ],
    flip: [
      { value: "no", label: "No" },
      { value: "yes", label: "Yes" },
    ],
    preloaded: [
      { value: "no", label: "No" },
      { value: "yes", label: "Yes" },
    ],
    dropSide: [
      { value: "right", label: "Right" },
      { value: "left", label: "Left" },
    ],
    showGuides: [
      { value: "yes", label: "Yes" },
      { value: "no", label: "No" },
    ],
  };

  const STORAGE_KEY_OLD = "bespoke-rube-builder-v1";
  const STORAGE_KEY = "bespoke-levels-v2";
  const BUILTIN_BLANK_ID = "builtin-blank";
  const BUILTIN_BLANK_NAME = "Blank";
  const BUILTIN_DEMO_ID = "builtin-perpetual-demo";
  const BUILTIN_DEMO_NAME = "Perpetual demo";
  const PART_DEFAULTS = {
    pill: { r: 17, restitution: 0.5, density: 0.001, friction: 0.06, frictionAir: 0.001, inertia: 2.5 },
    ramp: { w: 220, h: 10, angle: -8 },
    wall: { w: 10, h: 180, angle: 0 },
    domino: { w: 12, h: 76, angle: 0, density: 0.002 },
    gear: { r: 42, speed: 0.014, driveMode: "motor" },
    elevator: { w: 34, h: 110, travel: 280, speed: 0.012, mode: "continuous", startPos: "middle", triggerAction: "permanent" },
    spring: { w: 96, h: 24, angle: -45, power: 0.5 },
    catapult: { w: 170, angle: -12, power: 0.052, flip: "no" },
    trigger: { r: 56, power: 0.09 },
    bucketLift: { w: 60, h: 200, speed: 3, bucketW: 36, buckets: 6, dropSide: "right", preloaded: "no", pillR: 12, pillBounce: 0.5, pillDensity: 0.001, pillFriction: 0.06, pillAir: 0.001, pillInertia: 2.5, showGuides: "yes" },
  };
  const FRAME_STEP = 1000 / 60;
  const SNAP_SIZE = 20;
  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
  const degToRad = (deg) => deg * Math.PI / 180;
  const radToDeg = (rad) => rad * 180 / Math.PI;
  const snapToGrid = (val) => Math.round(val / SNAP_SIZE) * SNAP_SIZE;
  function refWidth() { return state.scene.designWidth || state.width; }
  function refHeight() { return state.scene.designHeight || state.height; }

  // Bucket tilt: Ferris wheel style — buckets stay upright everywhere,
  // only tipping briefly at the top to dump their payload.
  // na = normalized angle on oval (0=top, PI=bottom, >PI=ascending left side)
  // Returns tilt in radians: 0=upright
  function bucketTilt(na) {
    const tz = Math.PI * 0.14; // tip zone half-width (~25°)
    const TWO_PI = Math.PI * 2;
    const peakTilt = Math.PI * 0.47; // max tip ~85°

    // Only tip at the top of the loop
    if (na >= TWO_PI - tz || na < tz) {
      let t;
      if (na >= TWO_PI - tz) t = (na - TWO_PI + tz) / (tz * 2);
      else t = (na + tz) / (tz * 2);
      // Bell curve: 0 → peak → 0
      return Math.sin(t * Math.PI) * peakTilt;
    }

    // Everywhere else: upright
    return 0;
  }

  const Matter = window.Matter;
  const {
    Body,
    Bodies,
    Composite,
    Constraint,
    Engine,
    Events,
    Sleeping,
    Vector,
    World,
  } = Matter || {};

  const state = {
    width: 0,
    height: 0,
    dpr: 1,
    mode: "edit",
    activeTool: "select",
    selectedId: null,
    drag: null,
    hoveredId: null,
    mousePos: null,
    assets: null,
    scene: { parts: [] },
    engine: null,
    world: null,
    bodiesById: new Map(),
    playMeta: new Map(),
    lastTime: 0,
    time: 0,
    rotorAngle: 0,
    rotorSpeed: 0,
    curtain: { progress: 0, dropColor: null, bgColor: null, dropping: false, cooldown: 0 },
    logoLayout: null,
    currentSlotId: null,
    panels: { leftCollapsed: false, rightCollapsed: false },
    physicsAccum: 0,
    sceneScale: 1,
    sceneOffset: { x: 0, y: 0 },
  };

  function defaultScene() {
    return {
      parts: [
        { id: "demo-pill-a", type: "pill", x: 160, y: 40, r: 12, restitution: 0.75, demoLoop: true },
        { id: "demo-top-shelf", type: "ramp", x: 200, y: 80, w: 140, h: 9, angle: 15 },
        { id: "demo-ramp-upper", type: "ramp", x: 260, y: 120, w: 200, h: 10, angle: 6 },
        { id: "demo-ramp-domino", type: "ramp", x: 420, y: 180, w: 50, h: 10, angle: 0 },
        { id: "demo-trigger-logo", type: "trigger", x: 900, y: 440, r: 66, power: 0.12 },
        { id: "ramp-h97arr", type: "ramp", x: 600, y: 220, w: 220, h: 10, angle: -8 },
        { id: "ramp-ql2b6y", type: "ramp", x: 880, y: 600, w: 220, h: 10, angle: 7 },
        { id: "catapult-xoyovp", type: "catapult", x: 1100, y: 720, w: 200, angle: -25, power: 0.0009 },
        { id: "gear-6en3z8", type: "gear", x: 800, y: 220, r: 42, speed: 0.014, driveMode: "free" },
        { id: "gear-ta8nii", type: "gear", x: -60, y: 300, r: 42, speed: 0.014, driveMode: "motor" },
        { id: "gear-q2t7it", type: "gear", x: 460, y: 320, r: 42, speed: -0.014, driveMode: "auto" },
        { id: "gear-6rght0", type: "gear", x: 360, y: 560, r: 42, speed: 0.014, driveMode: "auto" },
        { id: "gear-epi6nr", type: "gear", x: 300, y: 620, r: 42, speed: -0.014, driveMode: "free" },
        { id: "ramp-n249js", type: "ramp", x: 320, y: 680, w: 220, h: 10, angle: -8 },
        { id: "bucketLift-yk2l0i", type: "bucketLift", x: 100, y: 420, w: 60, h: 380, speed: 3, bucketW: 36, buckets: 6, preloaded: "yes" },
      ],
    };
  }

  function blankScene() {
    return { parts: [] };
  }

  function loadImage(src) {
    return new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve(image);
      image.onerror = reject;
      image.src = src;
    });
  }

  Promise.all([
    loadImage(ASSETS.logoBase),
    loadImage(ASSETS.logoFull),
    loadImage(ASSETS.pill),
  ]).then(([logoBase, logoFull, pill]) => {
    state.assets = { logoBase, logoFull, pill };
    if (IS_VIEWER) {
      bootViewer();
    } else {
      bindUI();
      buildPalette();
      state.scene = defaultScene();
      state.currentSlotId = BUILTIN_DEMO_ID;
      resize();
      renderInspector();
      renderLevelList();
      requestAnimationFrame(tick);
    }
  });

  function bootViewer() {
    window.addEventListener("resize", resize);
    if (window.ResizeObserver) {
      new ResizeObserver(() => resize()).observe(canvas);
    }
    fetch(assetUrl("level.json"))
      .then((r) => r.json())
      .then((data) => {
        state.scene = normalizeScene(data);
        state.mode = "preview";
        resize();
        computeLogoLayout();
        buildWorld();
        requestAnimationFrame(tick);
      })
      .catch(() => {
        state.scene = defaultScene();
        state.mode = "preview";
        resize();
        computeLogoLayout();
        buildWorld();
        requestAnimationFrame(tick);
      });
  }

  function bindUI() {
    window.addEventListener("resize", resize);
    if (window.ResizeObserver) {
      const observer = new ResizeObserver(() => resize());
      observer.observe(canvas);
      observer.observe(builderEl);
    }
    canvas.addEventListener("pointerdown", onPointerDown);
    canvas.addEventListener("pointermove", onPointerMove);
    canvas.addEventListener("pointerup", onPointerUp);
    canvas.addEventListener("pointerleave", onPointerUp);
    window.addEventListener("keydown", onKeyDown);

    ui.edit.addEventListener("click", () => setMode("edit"));
    ui.play.addEventListener("click", () => setMode("play"));
    ui.reset.addEventListener("click", () => {
      loadBlankLevel();
    });
    ui.preview.addEventListener("click", enterPreview);
    ui.saveNew.addEventListener("click", () => saveToNewSlot());
    ui.download.addEventListener("click", downloadLevel);
    ui.upload.addEventListener("click", uploadLevel);
    ui.delete.addEventListener("click", deleteSelected);
    ui.toggleLeftPanel.addEventListener("click", () => togglePanel("left"));
    ui.toggleRightPanel.addEventListener("click", () => togglePanel("right"));

    migrateOldStorage();
    syncPanelChrome();
    renderLevelList();
  }

  function buildPalette() {
    if (!palette) return;
    palette.innerHTML = "";
    TOOLS.forEach((tool) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = `tool-button${tool.type === state.activeTool ? " is-active" : ""}`;
      button.dataset.tool = tool.type;
      button.innerHTML = `<span class="tool-icon">${tool.icon}</span><span class="tool-label">${tool.label}</span>`;
      button.addEventListener("click", () => {
        state.activeTool = tool.type;
        buildPalette();
      });
      palette.appendChild(button);
    });
  }

  function setMode(mode) {
    state.mode = mode;
    if (ui.edit) ui.edit.classList.toggle("is-active", mode === "edit");
    if (ui.play) ui.play.classList.toggle("is-active", mode === "play");
    syncPanelChrome();

    if (!state.scene.designWidth && state.width > 0) {
      state.scene.designWidth = state.width;
      state.scene.designHeight = state.height;
    }
    computeSceneScale();
    computeLogoLayout();

    if (mode === "play") {
      buildWorld();
    } else {
      clearWorld();
    }
    renderInspector();
  }

  function togglePanel(side) {
    if (side === "left") {
      state.panels.leftCollapsed = !state.panels.leftCollapsed;
    } else {
      state.panels.rightCollapsed = !state.panels.rightCollapsed;
    }
    syncPanelChrome();
    requestAnimationFrame(() => resize());
  }

  function syncPanelChrome() {
    if (IS_VIEWER) return;
    builderEl.classList.toggle("is-left-collapsed", state.panels.leftCollapsed);
    builderEl.classList.toggle("is-right-collapsed", state.panels.rightCollapsed);

    ui.toggleLeftPanel.textContent = state.panels.leftCollapsed ? "›" : "‹";
    ui.toggleLeftPanel.title = state.panels.leftCollapsed ? "Show tools panel" : "Hide tools panel";
    ui.toggleLeftPanel.setAttribute("aria-pressed", String(state.panels.leftCollapsed));

    ui.toggleRightPanel.textContent = state.panels.rightCollapsed ? "‹" : "›";
    ui.toggleRightPanel.title = state.panels.rightCollapsed ? "Show inspector panel" : "Hide inspector panel";
    ui.toggleRightPanel.setAttribute("aria-pressed", String(state.panels.rightCollapsed));
  }

  let previewExitBtn = null;

  function enterPreview() {
    state.mode = "preview";
    state.selectedId = null;
    builderEl.classList.add("is-preview");
    resize();
    requestAnimationFrame(() => {
      if (state.mode === "preview") resize();
    });

    previewExitBtn = document.createElement("button");
    previewExitBtn.className = "preview-exit";
    previewExitBtn.type = "button";
    previewExitBtn.textContent = "✕ Exit";
    previewExitBtn.addEventListener("click", exitPreview);
    document.body.appendChild(previewExitBtn);
  }

  function exitPreview() {
    builderEl.classList.remove("is-preview");
    if (previewExitBtn) {
      previewExitBtn.remove();
      previewExitBtn = null;
    }
    setMode("edit");
    resize();
  }

  function normalizeScene(scene) {
    const result = {
      parts: Array.isArray(scene.parts) ? scene.parts.map((part) => ({ ...part, id: part.id || createId(part.type) })) : [],
    };
    if (scene.designWidth) result.designWidth = scene.designWidth;
    if (scene.designHeight) result.designHeight = scene.designHeight;
    if (Array.isArray(scene.curtainColors)) result.curtainColors = scene.curtainColors.slice();
    return result;
  }

  function resize() {
    const rect = canvas.getBoundingClientRect();
    state.width = rect.width;
    state.height = rect.height;
    state.dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.floor(rect.width * state.dpr);
    canvas.height = Math.floor(rect.height * state.dpr);
    if (!state.scene.designWidth) {
      state.scene.designWidth = state.width;
      state.scene.designHeight = state.height;
    }
    computeSceneScale();
    computeLogoLayout();
    if (state.mode === "play" || state.mode === "preview") buildWorld();
  }

  function computeSceneScale() {
    const dw = refWidth();
    const dh = refHeight();
    const scale = Math.min(state.width / dw, state.height / dh);
    state.sceneScale = scale;
    state.sceneOffset = {
      x: (state.width - dw * scale) / 2,
      y: (state.height - dh * scale) / 2,
    };
  }

  function computeLogoLayout() {
    const stage = stageSize();
    const logoWidth = Math.min(stage.width * 0.76, 920);
    const logoScale = logoWidth / LOGO.width;
    const logoHeight = LOGO.height * logoScale;
    const logoX = stage.x + (stage.width - logoWidth) / 2;
    const centerY = stage.y + stage.height * 0.52;
    const logoY = centerY - logoHeight * 0.5;

    state.logoLayout = {
      logoX,
      logoY,
      logoWidth,
      logoHeight,
      logoScale,
      rotorX: logoX + (LOGO.pillX + LOGO.pillWidth * 0.5) * logoScale,
      rotorY: logoY + (LOGO.pillY + LOGO.pillHeight * 0.5) * logoScale,
      rotorW: LOGO.pillWidth * logoScale,
      rotorH: LOGO.pillHeight * logoScale,
    };
  }

  function stageSize() {
    return {
      x: 0,
      y: 0,
      width: refWidth(),
      height: refHeight(),
    };
  }

  function simulatedPart(part) {
    return part;
  }

  function buildWorld() {
    clearWorld();
    state.engine = Engine.create({
      enableSleeping: true,
      positionIterations: 10,
      velocityIterations: 8,
    });
    state.world = state.engine.world;
    state.world.gravity.y = 0.9;
    state.bodiesById.clear();
    state.playMeta.clear();
    state.physicsAccum = 0;

    const rw = refWidth(), rh = refHeight();
    World.add(state.world, [
      Bodies.rectangle(rw / 2, rh + 40, rw * 1.4, 80, { isStatic: true }),
      Bodies.rectangle(rw / 2, -40, rw * 1.4, 80, { isStatic: true }),
      Bodies.rectangle(-40, rh / 2, 80, rh * 1.4, { isStatic: true }),
      Bodies.rectangle(rw + 40, rh / 2, 80, rh * 1.4, { isStatic: true }),
    ]);

    state.scene.parts.forEach((part) => createMatterPart(simulatedPart(part)));

    Events.on(state.engine, "collisionStart", (event) => {
      event.pairs.forEach((pair) => handleCollision(pair.bodyA, pair.bodyB));
    });
    Events.on(state.engine, "collisionActive", (event) => {
      event.pairs.forEach((pair) => handleCollision(pair.bodyA, pair.bodyB));
    });
  }

  function clearWorld() {
    if (!state.engine) return;
    World.clear(state.world, false);
    Engine.clear(state.engine);
    state.engine = null;
    state.world = null;
    state.bodiesById.clear();
    state.playMeta.clear();
  }

  function createMatterPart(part) {
    if (part.type === "ramp" || part.type === "wall") {
      const body = Bodies.rectangle(part.x, part.y, part.w, part.h, {
        isStatic: true,
        angle: degToRad(part.angle || 0),
        friction: 0.05,
        frictionStatic: 0.01,
        restitution: 0.3,
      });
      registerBody(part, body);
      return;
    }

    if (part.type === "domino") {
      const body = Bodies.rectangle(part.x, part.y, part.w, part.h, {
        angle: degToRad(part.angle || 0),
        density: Number(part.density) || 0.002,
        friction: 0.08,
        frictionStatic: 0.02,
        restitution: 0.24,
      });
      registerBody(part, body);
      return;
    }

    if (part.type === "pill") {
      const pDensity = Number(part.density) || 0.001;
      const pFriction = Number(part.friction) || 0.06;
      const pAir = Number(part.frictionAir) || 0.001;
      const pInertia = Number(part.inertia) || 2.5;
      const body = Bodies.circle(part.x, part.y, part.r, {
        density: pDensity,
        friction: pFriction,
        frictionStatic: 0.005,
        frictionAir: pAir,
        restitution: clamp(Number(part.restitution) || 0.5, 0, 1),
        slop: 0.02,
      });
      Body.setInertia(body, body.inertia * pInertia);
      registerBody(part, body);
      return;
    }

    if (part.type === "gear") {
      const drive = part.driveMode || "motor";
      const airFriction = drive === "free" ? 0.01 : 0.04;
      const hub = Bodies.circle(part.x, part.y, part.r * 0.18, { density: 0.0008 });
      const barA = Bodies.rectangle(part.x, part.y, part.r * 1.9, 7, { density: 0.0008 });
      const barB = Bodies.rectangle(part.x, part.y, 7, part.r * 1.9, { density: 0.0008 });
      const body = Body.create({ parts: [hub, barA, barB], frictionAir: airFriction, restitution: 0.42 });
      const constraint = Constraint.create({
        bodyA: body,
        pointB: { x: part.x, y: part.y },
        stiffness: 1,
        length: 0,
      });
      registerBody(part, body);
      World.add(state.world, constraint);
      state.playMeta.set(part.id, { constraint });
      return;
    }

    if (part.type === "elevator") {
      const travel = Number(part.travel) || 200;
      const startPos = part.startPos || "middle";
      const mode = part.mode || "continuous";

      let startY, initialPhase, endY;
      if (startPos === "top") {
        startY = part.y - travel * 0.5;
        endY = part.y + travel * 0.5;
        initialPhase = -Math.PI / (2 * 0.02);
      } else if (startPos === "bottom") {
        startY = part.y + travel * 0.5;
        endY = part.y - travel * 0.5;
        initialPhase = Math.PI / (2 * 0.02);
      } else {
        startY = part.y;
        endY = part.y + travel * 0.5;
        initialPhase = 0;
      }

      const body = Bodies.rectangle(part.x, startY, part.w, part.h, {
        isStatic: true,
        friction: 0.8,
        restitution: 0.1,
      });
      registerBody(part, body);
      state.playMeta.set(part.id, {
        baseY: part.y,
        phase: initialPhase,
        active: mode === "continuous",
        startY,
        endY,
        t: 0,
        done: false,
      });
      return;
    }

    if (part.type === "spring") {
      const body = Bodies.rectangle(part.x, part.y, part.w, part.h, {
        isStatic: true,
        isSensor: true,
        angle: degToRad(part.angle || 0),
      });
      registerBody(part, body);
      return;
    }

    if (part.type === "catapult") {
      const dir = part.flip === "yes" ? -1 : 1;
      const body = Bodies.rectangle(part.x, part.y, part.w, 8, {
        density: 0.004,
        frictionAir: 0.08,
        angle: degToRad(part.angle || 0),
      });
      const constraint = Constraint.create({
        bodyA: body,
        pointA: { x: -part.w * 0.32 * dir, y: 0 },
        pointB: { x: part.x - part.w * 0.32 * dir, y: part.y },
        stiffness: 0.98,
        damping: 0.05,
        length: 0,
      });
      registerBody(part, body);
      World.add(state.world, constraint);
      state.playMeta.set(part.id, { constraint, cooldown: 0 });
      return;
    }

    if (part.type === "trigger") {
      const body = Bodies.circle(part.x, part.y, part.r, {
        isStatic: true,
        isSensor: true,
      });
      registerBody(part, body);
      return;
    }

    if (part.type === "bucketLift") {
      const a = Number(part.w) || 60;
      const b = Number(part.h) || 200;
      const bw = Number(part.bucketW) || 36;
      const NUM_BUCKETS = clamp(Math.round(Number(part.buckets) || 6), 2, 12);
      const body = Bodies.rectangle(part.x, part.y, a * 2 + bw, b * 2, {
        isStatic: true,
        isSensor: true,
      });
      registerBody(part, body);

      const attachments = Array(NUM_BUCKETS).fill(null);

      // Preload: create a pill in every bucket
      if (part.preloaded === "yes") {
        for (let i = 0; i < NUM_BUCKETS; i++) {
          const angle = (i * Math.PI * 2) / NUM_BUCKETS;
          const na = ((angle % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
          const tilt = bucketTilt(na);
          const bx = part.x + a * Math.sin(angle);
          const by = part.y - b * Math.cos(angle);
          const pr = clamp(Number(part.pillR) || 12, 4, 40);
          const pBounce = clamp(Number(part.pillBounce) || 0.7, 0, 1);
          const pDensity = Number(part.pillDensity) || 0.001;
          const pFriction = Number(part.pillFriction) || 0.06;
          const pAir = Number(part.pillAir) || 0.001;
          const pInertia = Number(part.pillInertia) || 2.5;
          const pillX = bx + Math.sin(tilt) * (pr + 4);
          const pillY = by - Math.cos(tilt) * (pr + 4);
          const pillId = `${part.id}-pill-${i}`;
          const pillBody = Bodies.circle(pillX, pillY, pr, {
            density: pDensity,
            friction: pFriction,
            frictionStatic: 0.005,
            frictionAir: pAir,
            restitution: pBounce,
            slop: 0.02,
          });
          Body.setInertia(pillBody, pillBody.inertia * pInertia);
          pillBody.plugin = { partId: pillId, type: "pill", pulse: 0, pillR: pr };
          state.bodiesById.set(pillId, pillBody);
          World.add(state.world, pillBody);
          attachments[i] = { pillId };
        }
      }

      state.playMeta.set(part.id, {
        phase: 0,
        bucketAttachments: attachments,
        releasedPills: new Map(), // pillId → release timestamp (cooldown)
      });
    }
  }

  function registerBody(part, body) {
    body.plugin = { partId: part.id, type: part.type, pulse: 0 };
    state.bodiesById.set(part.id, body);
    World.add(state.world, body);
  }

  function handleCollision(bodyA, bodyB) {
    const partA = getPart(bodyA.plugin?.partId) || bodyA.plugin;
    const partB = getPart(bodyB.plugin?.partId) || bodyB.plugin;
    if (!partA || !partB) return;

    if (bodyA.plugin) bodyA.plugin.pulse = 1;
    if (bodyB.plugin) bodyB.plugin.pulse = 1;

    applyTrigger(partA, bodyB);
    applyTrigger(partB, bodyA);
    applySpring(partA, bodyB);
    applySpring(partB, bodyA);
    applyElevatorTrigger(partA, bodyB);
    applyElevatorTrigger(partB, bodyA);
  }

  function applyTrigger(triggerPart, otherBody) {
    if (triggerPart.type !== "trigger" || !isDynamicBody(otherBody)) return;
    state.rotorSpeed += (Number(triggerPart.power) || 0.08) * 0.05;
    state.rotorSpeed = clamp(state.rotorSpeed, -0.24, 0.24);
    if (!state.curtain.dropping && state.curtain.cooldown <= 0) {
      state.curtain.dropping = true;
      state.curtain.progress = 0;
      state.curtain.dropColor = nextCurtainColor(state.curtain.bgColor);
    }
  }

  function applySpring(springPart, otherBody) {
    if (springPart.type !== "spring" || !isDynamicBody(otherBody)) return;
    const angle = degToRad(springPart.angle || 0) - Math.PI / 2;
    const power = Number(springPart.power) || 0.5;
    Body.applyForce(otherBody, otherBody.position, {
      x: Math.cos(angle) * power,
      y: Math.sin(angle) * power,
    });
  }

  function applyElevatorTrigger(elevatorPart, otherBody) {
    if (elevatorPart.type !== "elevator") return;
    if ((elevatorPart.mode || "continuous") !== "trigger") return;
    if (!isDynamicBody(otherBody)) return;
    const meta = state.playMeta.get(elevatorPart.id);
    if (!meta || meta.active) return;
    meta.active = true;
  }

  function isDynamicBody(body) {
    return body && !body.isStatic && !body.isSensor;
  }

  function tick(now) {
    const raw = state.lastTime ? (now - state.lastTime) : 16.666;
    state.lastTime = now;
    const delta = clamp(raw, 1, 100);
    state.time += delta / 16.666;
    update(delta);
    draw();
    requestAnimationFrame(tick);
  }

  function update(delta) {
    const dtScale = delta / 16.666;
    state.rotorSpeed *= Math.pow(0.992, dtScale);
    state.rotorAngle += state.rotorSpeed * dtScale;
    if (state.curtain.cooldown > 0) state.curtain.cooldown -= dtScale;
    if (state.curtain.dropping) {
      state.curtain.progress += 0.006 * dtScale;
      if (state.curtain.progress >= 1) {
        state.curtain.bgColor = state.curtain.dropColor;
        state.curtain.dropColor = null;
        state.curtain.progress = 0;
        state.curtain.dropping = false;
        state.curtain.cooldown = 120; // ~2 seconds before next curtain can start
      }
    }

    if ((state.mode !== "play" && state.mode !== "preview") || !state.engine) return;

    const STEP = 16.666;
    state.physicsAccum += delta;
    if (state.physicsAccum > STEP * 6) state.physicsAccum = STEP * 6;
    while (state.physicsAccum >= STEP) {
      updateMechanisms(STEP);
      sanitizeBodies();
      Engine.update(state.engine, STEP);
      state.physicsAccum -= STEP;
    }
    stabilizeDemoBodies(0);
  }

  function updateMechanisms(delta) {
    state.scene.parts.forEach((part) => {
      const simPart = simulatedPart(part);
      const body = state.bodiesById.get(part.id);
      const meta = state.playMeta.get(part.id);
      if (!body) return;

      body.plugin.pulse *= 0.88;

      if (simPart.type === "gear") {
        const drive = simPart.driveMode || "motor";
        Body.setPosition(body, { x: simPart.x, y: simPart.y });
        Body.setVelocity(body, { x: 0, y: 0 });
        if (drive === "motor") {
          Body.setAngularVelocity(body, (body.angularVelocity * 0.88) + (Number(simPart.speed) || 0));
        } else if (drive === "auto") {
          const targetSpeed = Number(simPart.speed) || 0;
          const diff = targetSpeed - body.angularVelocity;
          Body.setAngularVelocity(body, body.angularVelocity + diff * 0.02);
        }
      }

      if (simPart.type === "elevator" && meta) {
        const mode = simPart.mode || "continuous";
        const travel = Number(simPart.travel) || 200;
        const speed = Number(simPart.speed) || 0.01;

        if (!meta.active || meta.done) return;

        if (mode === "continuous" || (mode === "trigger" && (simPart.triggerAction || "permanent") === "permanent")) {
          meta.phase += speed * delta;
          const y = meta.baseY + Math.sin(meta.phase * 0.02) * travel * 0.5;
          Body.setPosition(body, { x: simPart.x, y });
        } else {
          const action = simPart.triggerAction || "permanent";
          meta.t = Math.min(meta.t + speed * delta * 0.02, 1);
          if (meta.t >= 1) meta.done = true;
          let y;
          if (action === "once") {
            y = meta.startY + (meta.endY - meta.startY) * (0.5 - 0.5 * Math.cos(meta.t * Math.PI));
          } else {
            y = meta.startY + (meta.endY - meta.startY) * (0.5 - 0.5 * Math.cos(meta.t * Math.PI * 2));
          }
          Body.setPosition(body, { x: simPart.x, y });
        }
      }

      if (simPart.type === "catapult") {
        if (!isFiniteBody(body)) {
          resetDemoBody(part, simPart, body);
          return;
        }
        const flipped = simPart.flip === "yes";
        const dir = flipped ? -1 : 1;
        Body.setPosition(body, { x: simPart.x, y: simPart.y });
        Body.setVelocity(body, { x: 0, y: 0 });
        meta.cooldown = Math.max(0, meta.cooldown - delta);
        const baseAngle = degToRad(simPart.angle || 0);
        const maxSwing = Math.PI * 0.4;
        if (body.angle < baseAngle - maxSwing) {
          Body.setAngle(body, baseAngle - maxSwing);
          Body.setAngularVelocity(body, Math.max(0, body.angularVelocity));
        } else if (body.angle > baseAngle + maxSwing) {
          Body.setAngle(body, baseAngle + maxSwing);
          Body.setAngularVelocity(body, Math.min(0, body.angularVelocity));
        }
        const cup = catapultCup(simPart, body, dir);
        const cupRadius = catapultCupRadius(simPart);
        state.bodiesById.forEach((candidate) => {
          if (!isDynamicBody(candidate) || meta.cooldown > 0) return;
          if (candidate.plugin?.type !== "pill") return;
          const dx = candidate.position.x - cup.x, dy = candidate.position.y - cup.y;
          const distance = Math.sqrt(dx * dx + dy * dy);
          if (distance > cupRadius) return;
          Sleeping.set(candidate, false);
          const power = Math.max(0.001, Number(simPart.power) || 0.05);
          // Normal: launch up-left. Flipped: mirror across vertical = up-right
          const launchAngle = flipped
            ? Math.PI * 1.58 - body.angle
            : body.angle - Math.PI * 0.58;
          const launchSpeed = clamp(8 + power * 430, 10, 14);
          Body.setVelocity(candidate, {
            x: Math.cos(launchAngle) * launchSpeed,
            y: Math.sin(launchAngle) * launchSpeed,
          });
          Body.setPosition(candidate, {
            x: cup.x + Math.cos(launchAngle) * (cupRadius * 0.55),
            y: cup.y + Math.sin(launchAngle) * (cupRadius * 0.55),
          });
          Body.setAngularVelocity(candidate, 0.55 * dir * Math.sign(Math.cos(body.angle) || 1));
          Body.setAngularVelocity(body, 0.52 * dir);
          body.plugin.pulse = 1;
          meta.cooldown = 760;
        });
      }

      if (simPart.type === "bucketLift" && meta) {
        const speed = Number(simPart.speed) || 0.008;
        const a = Number(simPart.w) || 60;
        const b = Number(simPart.h) || 200;
        const bw = Number(simPart.bucketW) || 36;
        const cx = simPart.x;
        const cy = simPart.y;
        const NUM_BUCKETS = clamp(Math.round(Number(simPart.buckets) || 6), 2, 12);

        meta.phase += speed * 0.0002 * delta;

        // Clean up expired cooldowns
        const now = performance.now();
        meta.releasedPills.forEach((time, id) => { if (now - time > 2000) meta.releasedPills.delete(id); });

        const attachedPills = new Set();
        meta.bucketAttachments.forEach((att) => { if (att) attachedPills.add(att.pillId); });

        // Pre-filter: collect free pills near this lift ONCE (avoid O(n) per bucket)
        const searchRadius = Math.max(a, b) + bw * 2;
        const nearbyPills = [];
        state.bodiesById.forEach((candidate) => {
          if (!isDynamicBody(candidate) || candidate.plugin?.type !== "pill") return;
          if (attachedPills.has(candidate.plugin.partId)) return;
          const releaseTime = meta.releasedPills.get(candidate.plugin.partId);
          if (releaseTime && now - releaseTime < 2000) return;
          const dx = candidate.position.x - cx, dy = candidate.position.y - cy;
          if (dx * dx + dy * dy < searchRadius * searchRadius) {
            nearbyPills.push(candidate);
          }
        });

        for (let i = 0; i < NUM_BUCKETS; i++) {
          const angle = meta.phase + (i * Math.PI * 2) / NUM_BUCKETS;
          const bx = cx + a * Math.sin(angle);
          const by = cy - b * Math.cos(angle);

          const na = ((angle % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
          const dropDir = simPart.dropSide === "left" ? -1 : 1;
          const tilt = bucketTilt(na) * dropDir;

          // Pill sits above the bucket floor in the bucket's local -Y direction
          const pr = clamp(Number(simPart.pillR) || 12, 4, 40);
          const pillDist = pr + 4;
          const pillX = bx + Math.sin(tilt) * pillDist;
          const pillY = by - Math.cos(tilt) * pillDist;

          // Pickup: when bucket is upright enough (|tilt| < ~54°)
          const absTilt = Math.abs(tilt);
          const canPickup = absTilt < Math.PI * 0.3;
          // Release: when bucket has tipped enough (|tilt| between 70° and 140°)
          const shouldRelease = absTilt > Math.PI * 0.39 && absTilt < Math.PI * 0.78;
          // Ascending phase: bucket is past the upper descent zone (safe to pick up)
          const isAscendingPhase = na > Math.PI * 0.4;

          // --- Pickup (uses pre-filtered nearbyPills) ---
          if (canPickup && isAscendingPhase && !meta.bucketAttachments[i]) {
            const pickupR = bw * 1.5;
            for (let j = 0; j < nearbyPills.length; j++) {
              const candidate = nearbyPills[j];
              if (meta.bucketAttachments[i]) break;
              if (attachedPills.has(candidate.plugin.partId)) continue;
              // Pill must be above or at bucket opening level
              if (candidate.position.y > by + bw * 0.4) continue;
              const dist = Math.hypot(candidate.position.x - bx, candidate.position.y - by);
              if (dist < pickupR) {
                meta.bucketAttachments[i] = { pillId: candidate.plugin.partId };
                attachedPills.add(candidate.plugin.partId);
                meta.releasedPills.delete(candidate.plugin.partId);
              }
            }
          }

          // --- Move or release attached pill ---
          if (meta.bucketAttachments[i]) {
            const att = meta.bucketAttachments[i];
            const pillBody = state.bodiesById.get(att.pillId);
            if (!pillBody) {
              meta.bucketAttachments[i] = null;
            } else if (shouldRelease) {
              // Wake up and eject in the tilt direction with momentum
              Sleeping.set(pillBody, false);
              const ejectSpeed = 2.5;
              Body.setVelocity(pillBody, {
                x: Math.sin(tilt) * ejectSpeed,
                y: Math.cos(tilt) * ejectSpeed * 0.5,
              });
              Body.setAngularVelocity(pillBody, 0);
              meta.releasedPills.set(att.pillId, now);
              meta.bucketAttachments[i] = null;
            } else {
              // Keep attached pill awake and in position
              Sleeping.set(pillBody, false);
              Body.setPosition(pillBody, { x: pillX, y: pillY });
              Body.setVelocity(pillBody, { x: 0, y: 0 });
              Body.setAngularVelocity(pillBody, 0);
            }
          }
        }
      }
    });
    updatePerpetualDemo(delta);
  }

  function updatePerpetualDemo(delta) {
    const demoPills = state.scene.parts.filter((part) => part.type === "pill" && part.demoLoop);
    if (!demoPills.length) return;

    const elevator = simulatedPart(getPart("demo-elevator") || {});
    const spring = simulatedPart(getPart("demo-spring") || {});
    const catapult = simulatedPart(getPart("demo-catapult") || {});
    const upperRamp = simulatedPart(getPart("demo-ramp-upper") || {});
    const lowerRamp = simulatedPart(getPart("demo-ramp-lower") || {});
    const chuteLeft = simulatedPart(getPart("demo-chute-left") || {});
    const chuteRight = simulatedPart(getPart("demo-chute-right") || {});

    const liftX = elevator.x || 80;
    const topY = upperRamp.y || 110;
    const bottomY = lowerRamp.y || (refHeight() - 100);
    const chuteX = ((chuteLeft.x || refWidth() - 120) + (chuteRight.x || refWidth() - 70)) * 0.5;
    const springX = spring.x || chuteX;
    const catapultX = catapult.x || liftX + 100;

    stabilizeDemoBodies(delta);

    demoPills.forEach((part) => {
      const body = state.bodiesById.get(part.id);
      if (!body) return;
      if (!isFiniteBody(body)) return;

      const force = { x: 0, y: 0 };
      const p = body.position;
      const routePower = 0.00016;

      if (p.y < topY + 110 && p.x < chuteX - 40) {
        force.x += routePower;
        force.y += 0.00004;
      } else if (p.x > chuteX - 84 && p.y < bottomY - 84) {
        force.x += (chuteX - p.x) * 0.000006;
        force.y += routePower * 0.72;
      } else if (p.y > bottomY - 134 && p.x > catapultX + 70) {
        force.x -= routePower * 0.95;
        force.y -= p.x > springX - 42 ? routePower * 0.28 : 0;
      } else if (p.x < liftX + 100 && p.y > topY + 70) {
        force.x += (liftX + 12 - p.x) * 0.000005;
        force.y -= routePower * 0.24;
      }

      if (Math.abs(force.x) > 0.000001 || Math.abs(force.y) > 0.000001) {
        Body.applyForce(body, body.position, force);
      }
    });
  }

  function stabilizeDemoBodies(delta) {
    state.scene.parts.forEach((part) => {
      if (!part.id.startsWith("demo-")) return;
      const body = state.bodiesById.get(part.id);
      if (!body) return;

      const simPart = simulatedPart(part);
      if (!isFiniteBody(body)) {
        resetDemoBody(part, simPart, body);
        return;
      }

      if (part.type === "gear") {
        Body.setPosition(body, { x: simPart.x, y: simPart.y });
        Body.setVelocity(body, { x: 0, y: 0 });
        return;
      }

      if (part.type === "catapult") {
        Body.setPosition(body, { x: simPart.x, y: simPart.y });
        Body.setVelocity(body, { x: 0, y: 0 });
        clampBodySpeed(body, 8);
        return;
      }

      if (part.type === "pill" && part.demoLoop) {
        clampBodySpeed(body, 14);
        return;
      }

      if (part.type !== "domino") return;
      clampBodySpeed(body, 7);
      const metaKey = `demo-reset:${part.id}`;
      const meta = state.playMeta.get(metaKey) || { toppledFor: 0 };
      const farAway = (
        Math.abs(body.position.x - simPart.x) > refWidth() * 0.65 ||
        Math.abs(body.position.y - simPart.y) > refHeight() * 0.75
      );
      const settledToppled = Math.abs(body.angle - degToRad(part.angle || 0)) > 0.82 && Vector.magnitude(body.velocity) < 0.16;
      meta.toppledFor = (farAway || settledToppled) ? meta.toppledFor + delta : 0;
      if (farAway || meta.toppledFor > 5200) {
        resetDemoBody(part, simPart, body);
        meta.toppledFor = 0;
      }
      state.playMeta.set(metaKey, meta);
    });
  }

  function isFiniteBody(body) {
    return (
      body &&
      Number.isFinite(body.position.x) &&
      Number.isFinite(body.position.y) &&
      Number.isFinite(body.velocity.x) &&
      Number.isFinite(body.velocity.y) &&
      Number.isFinite(body.angle) &&
      Number.isFinite(body.angularVelocity)
    );
  }

  function clampBodySpeed(body, maxSpeed) {
    const vx = body.velocity.x, vy = body.velocity.y;
    const speedSq = vx * vx + vy * vy;
    if (!Number.isFinite(speedSq) || speedSq <= maxSpeed * maxSpeed) return;
    const scale = maxSpeed / Math.sqrt(speedSq);
    Body.setVelocity(body, { x: vx * scale, y: vy * scale });
  }

  const _oobRemoveQueue = [];
  function sanitizeBodies() {
    const MAX_SPEED = 14;
    const MAX_ANGULAR = 0.4;
    const rw = refWidth(), rh = refHeight();
    const margin = 400;
    _oobRemoveQueue.length = 0;
    state.bodiesById.forEach((body, id) => {
      if (body.isStatic || body.isSensor) return;
      if (!isFiniteBody(body)) {
        Body.setPosition(body, { x: rw * 0.5, y: 0 });
        Body.setAngle(body, 0);
        Body.setVelocity(body, { x: 0, y: 0 });
        Body.setAngularVelocity(body, 0);
        return;
      }
      // Remove pills that fell far out of bounds
      const px = body.position.x, py = body.position.y;
      if (body.plugin?.type === "pill" && (px < -margin || px > rw + margin || py < -margin || py > rh + margin)) {
        _oobRemoveQueue.push(id);
        return;
      }
      clampBodySpeed(body, MAX_SPEED);
      if (Math.abs(body.angularVelocity) > MAX_ANGULAR) {
        Body.setAngularVelocity(body, Math.sign(body.angularVelocity) * MAX_ANGULAR);
      }
    });
    // Remove out-of-bounds pills outside the iterator
    for (let i = 0; i < _oobRemoveQueue.length; i++) {
      const id = _oobRemoveQueue[i];
      const body = state.bodiesById.get(id);
      if (body) {
        World.remove(state.world, body);
        state.bodiesById.delete(id);
      }
    }
  }

  function resetDemoBody(part, simPart, body) {
    if (part.type === "domino") {
      Body.setPosition(body, { x: simPart.x, y: simPart.y });
      Body.setAngle(body, degToRad(part.angle || 0));
      Body.setVelocity(body, { x: 0, y: 0 });
      Body.setAngularVelocity(body, 0);
      return;
    }

    if (part.type === "catapult") {
      Body.setPosition(body, { x: simPart.x, y: simPart.y });
      Body.setAngle(body, degToRad(simPart.angle || 0));
      Body.setVelocity(body, { x: 0, y: 0 });
      Body.setAngularVelocity(body, 0);
      return;
    }

    if (part.type === "gear") {
      Body.setPosition(body, { x: simPart.x, y: simPart.y });
      Body.setVelocity(body, { x: 0, y: 0 });
      Body.setAngularVelocity(body, 0);
    }
  }

  function drawCurtain() {
    const c = state.curtain;
    if (!c.bgColor && !c.dropColor) return;
    ctx.save();
    ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
    const sw = state.width;
    const sh = state.height;

    if (c.dropColor && c.progress > 0) {
      const curtainH = Math.round(sh * c.progress);
      // New curtain covers top portion
      ctx.fillStyle = c.dropColor;
      ctx.fillRect(0, 0, sw, curtainH);
      // Soft bottom edge — use cached gradient when position hasn't changed
      const edgeH = 50;
      if (c._gradY !== curtainH || c._gradColor !== c.dropColor) {
        c._grad = ctx.createLinearGradient(0, curtainH, 0, curtainH + edgeH);
        c._grad.addColorStop(0, c.dropColor);
        c._grad.addColorStop(1, "rgba(0,0,0,0)");
        c._gradY = curtainH;
        c._gradColor = c.dropColor;
      }
      ctx.fillStyle = c._grad;
      ctx.fillRect(0, curtainH, sw, edgeH);
      // Previous color only below the curtain (no overlap)
      if (c.bgColor) {
        ctx.fillStyle = c.bgColor;
        ctx.fillRect(0, curtainH, sw, sh - curtainH);
      }
    } else if (c.bgColor) {
      // No active drop — just the settled background
      ctx.fillStyle = c.bgColor;
      ctx.fillRect(0, 0, sw, sh);
    }

    ctx.restore();
  }

  function draw() {
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawCurtain();
    const s = state.sceneScale;
    const ox = state.sceneOffset.x;
    const oy = state.sceneOffset.y;
    ctx.setTransform(s * state.dpr, 0, 0, s * state.dpr, ox * state.dpr, oy * state.dpr);
    drawGrid();
    drawLogo();

    if (state.mode === "play" || state.mode === "preview") {
      drawPlayParts();
    } else {
      drawEditParts();
    }

    if (state.mode === "edit") {
      drawSafeFrame();
      drawSelection();
      drawHover();
      drawPlacementPreview();
    }
  }

  function drawSafeFrame() {
    const rw = refWidth();
    const rh = refHeight();
    const cx = rw / 2;
    const cy = rh / 2;
    const corner = 24;

    // Border
    ctx.save();
    ctx.strokeStyle = "rgba(218, 22, 122, 0.22)";
    ctx.lineWidth = 1.5;
    ctx.setLineDash([6, 4]);
    ctx.strokeRect(0, 0, rw, rh);
    ctx.setLineDash([]);

    // Corner marks
    ctx.strokeStyle = "rgba(218, 22, 122, 0.45)";
    ctx.lineWidth = 2;
    ctx.lineCap = "round";

    // TL
    ctx.beginPath();
    ctx.moveTo(0, corner); ctx.lineTo(0, 0); ctx.lineTo(corner, 0);
    ctx.stroke();
    // TR
    ctx.beginPath();
    ctx.moveTo(rw - corner, 0); ctx.lineTo(rw, 0); ctx.lineTo(rw, corner);
    ctx.stroke();
    // BL
    ctx.beginPath();
    ctx.moveTo(0, rh - corner); ctx.lineTo(0, rh); ctx.lineTo(corner, rh);
    ctx.stroke();
    // BR
    ctx.beginPath();
    ctx.moveTo(rw - corner, rh); ctx.lineTo(rw, rh); ctx.lineTo(rw, rh - corner);
    ctx.stroke();

    // Center crosshair
    ctx.strokeStyle = "rgba(218, 22, 122, 0.13)";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(cx - 10, cy); ctx.lineTo(cx + 10, cy);
    ctx.moveTo(cx, cy - 10); ctx.lineTo(cx, cy + 10);
    ctx.stroke();

    ctx.restore();
  }

  function drawGrid() {
    if (state.mode === "preview") return;
    ctx.save();
    ctx.strokeStyle = "rgba(29, 29, 27, 0.045)";
    ctx.lineWidth = 1;
    const step = 40;
    const rw = refWidth(), rh = refHeight();
    for (let x = 0; x < rw; x += step) {
      ctx.beginPath();
      ctx.moveTo(x, 0);
      ctx.lineTo(x, rh);
      ctx.stroke();
    }
    for (let y = 0; y < rh; y += step) {
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(rw, y);
      ctx.stroke();
    }
    ctx.restore();
  }

  function drawLogo() {
    const L = state.logoLayout;
    if (!L) return;

    // Logo image
    ctx.save();
    ctx.globalAlpha = 0.95;
    ctx.shadowColor = "rgba(29, 29, 27, 0.07)";
    ctx.shadowBlur = 20;
    ctx.shadowOffsetY = 12;
    ctx.drawImage(state.assets.logoBase, L.logoX, L.logoY, L.logoWidth, L.logoHeight);
    ctx.restore();

    // Rotor pill
    ctx.save();
    ctx.translate(L.rotorX, L.rotorY);
    ctx.rotate(state.rotorAngle);
    ctx.shadowColor = "rgba(218, 22, 122, 0.24)";
    ctx.shadowBlur = Math.min(Math.round(18 + Math.abs(state.rotorSpeed) * 60), 40);
    ctx.drawImage(state.assets.pill, -L.rotorW * 0.5, -L.rotorH * 0.5, L.rotorW, L.rotorH);
    ctx.restore();
  }

  function drawEditParts() {
    state.scene.parts.forEach((part) => drawPart(part, null));
  }

  const _sceneIdSet = new Set();
  function drawPlayParts() {
    _sceneIdSet.clear();
    state.scene.parts.forEach((part) => {
      _sceneIdSet.add(part.id);
      const body = state.bodiesById.get(part.id);
      drawPart(simulatedPart(part), body);
    });
    // Draw preloaded pills (physics bodies with no scene part)
    state.bodiesById.forEach((body, id) => {
      if (_sceneIdSet.has(id)) return;
      if (body.plugin?.type !== "pill") return;
      drawPill(body.position.x, body.position.y, body.angle, body.plugin.pillR || 12, body.plugin.pulse || 0);
    });
  }

  function drawPart(part, body) {
    const x = body ? body.position.x : part.x;
    const y = body ? body.position.y : part.y;
    const angle = body ? body.angle : degToRad(part.angle || 0);
    const pulse = body?.plugin?.pulse || 0;

    if (part.type === "pill") {
      drawPill(x, y, body ? body.angle : 0, part.r, pulse);
      return;
    }

    if (part.type === "ramp" || part.type === "wall" || part.type === "domino") {
      drawRectanglePart(part, x, y, angle, pulse);
      return;
    }

    if (part.type === "gear") {
      drawGear(x, y, part.r, angle, part.strokeColor || (part.speed < 0 ? COLORS.yellowRail : COLORS.pinkRail), 0.86);
      return;
    }

    if (part.type === "elevator") {
      drawElevator(part, x, y, pulse);
      return;
    }

    if (part.type === "spring") {
      drawSpring(part, x, y, angle, pulse);
      return;
    }

    if (part.type === "catapult") {
      drawCatapult(part, x, y, angle, pulse);
      return;
    }

    if (part.type === "trigger") {
      drawTrigger(part, x, y, pulse);
      return;
    }

    if (part.type === "bucketLift") {
      drawBucketLift(part, x, y);
    }
  }

  function drawRectanglePart(part, x, y, angle, pulse) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.fillStyle = part.fillColor || (part.type === "domino" ? "rgba(218, 22, 122, 0.11)" : "rgba(29, 29, 27, 0.06)");
    ctx.strokeStyle = part.strokeColor || (part.type === "ramp" ? COLORS.yellowRail : part.type === "domino" ? COLORS.pinkRail : COLORS.rail);
    ctx.lineWidth = 2 + pulse * 2;
    if (pulse > 0.05) {
      ctx.shadowColor = ctx.strokeStyle;
      ctx.shadowBlur = Math.round(pulse * 12);
    }
    ctx.beginPath();
    ctx.rect(-part.w * 0.5, -part.h * 0.5, part.w, part.h);
    ctx.fill();
    ctx.stroke();
    ctx.restore();
  }

  function drawPill(x, y, angle, r, pulse) {
    const size = r * 2.25;
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    if (pulse > 0.05) {
      ctx.shadowColor = "rgba(218, 22, 122, 0.24)";
      ctx.shadowBlur = Math.round(12 + pulse * 14);
    } else {
      ctx.shadowColor = "rgba(218, 22, 122, 0.18)";
      ctx.shadowBlur = 10;
    }
    ctx.drawImage(state.assets.pill, -size * 0.5, -size * 0.5, size, size);
    ctx.restore();
  }

  function drawGear(x, y, radius, rotation, stroke, alpha) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(rotation);
    ctx.globalAlpha = alpha;
    ctx.strokeStyle = stroke;
    ctx.lineWidth = 2.5;
    ctx.lineCap = "round";
    ctx.beginPath();
    ctx.moveTo(-radius * 0.82, 0);
    ctx.lineTo(radius * 0.82, 0);
    ctx.moveTo(0, -radius * 0.82);
    ctx.lineTo(0, radius * 0.82);
    ctx.stroke();
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.arc(0, 0, radius * 0.22, 0, Math.PI * 2);
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, 0, radius, 0, Math.PI * 2);
    ctx.globalAlpha = alpha * 0.3;
    ctx.stroke();
    ctx.restore();
  }

  function drawElevator(part, x, y, pulse) {
    ctx.save();
    const mode = part.mode || "continuous";
    const meta = state.playMeta.get(part.id);
    const isActive = mode === "continuous" || (meta && meta.active);

    // Travel range tick marks
    const travel = Number(part.travel) || 200;
    const tickW = part.w * 0.35;
    ctx.strokeStyle = COLORS.railSoft;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(part.x - tickW, part.y - travel * 0.5);
    ctx.lineTo(part.x + tickW, part.y - travel * 0.5);
    ctx.moveTo(part.x - tickW, part.y + travel * 0.5);
    ctx.lineTo(part.x + tickW, part.y + travel * 0.5);
    ctx.stroke();

    // Platform
    ctx.translate(x, y);
    ctx.fillStyle = part.fillColor || (isActive ? "rgba(218, 22, 122, 0.08)" : "rgba(29, 29, 27, 0.05)");
    ctx.strokeStyle = part.strokeColor || (isActive ? COLORS.pinkRail : COLORS.rail);
    ctx.lineWidth = 2 + pulse * 1.5;
    ctx.fillRect(-part.w * 0.5, -part.h * 0.5, part.w, part.h);
    ctx.strokeRect(-part.w * 0.5, -part.h * 0.5, part.w, part.h);
    ctx.restore();
  }

  function drawSpring(part, x, y, angle, pulse) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.strokeStyle = part.strokeColor || COLORS.yellowRail;
    ctx.lineWidth = 2 + pulse * 1.5;
    ctx.beginPath();
    for (let i = 0; i <= 8; i += 1) {
      const px = -part.w * 0.5 + (i / 8) * part.w;
      const py = (i % 2 === 0 ? -1 : 1) * part.h;
      if (i === 0) ctx.moveTo(px, 0);
      ctx.lineTo(px, py);
    }
    ctx.stroke();
    ctx.restore();
  }

  function drawCatapult(part, x, y, angle, pulse) {
    const dir = part.flip === "yes" ? -1 : 1;
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.strokeStyle = part.strokeColor || COLORS.rail;
    ctx.lineWidth = 5;
    ctx.lineCap = "round";
    ctx.beginPath();
    ctx.moveTo(-part.w * 0.5, 0);
    ctx.lineTo(part.w * 0.5, 0);
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(part.w * 0.5 * dir, 0, catapultCupRadius(part) + pulse * 4, 0, Math.PI * 2);
    ctx.strokeStyle = part.strokeColor || COLORS.pinkRail;
    ctx.lineWidth = 2;
    ctx.stroke();
    ctx.restore();
  }

  function drawTrigger(part, x, y, pulse) {
    ctx.save();
    ctx.translate(x, y);
    ctx.strokeStyle = part.strokeColor || COLORS.pinkRail;
    ctx.lineWidth = 1.5 + pulse * 2;
    ctx.setLineDash([2, 8]);
    ctx.beginPath();
    ctx.arc(0, 0, part.r + pulse * 8, 0, Math.PI * 2);
    ctx.stroke();
    ctx.restore();
  }

  function drawBucketLift(part, x, y) {
    const a = Number(part.w) || 60;
    const b = Number(part.h) || 200;
    const bw = Number(part.bucketW) || 36;
    const meta = state.playMeta.get(part.id);
    const phase = meta ? meta.phase : 0;
    const NUM_BUCKETS = clamp(Math.round(Number(part.buckets) || 6), 2, 12);
    const isPlaying = state.mode === "play" || state.mode === "preview";

    ctx.save();

    if (part.showGuides !== "no") {
      // Draw two vertical rails (like real bucket elevator tracks)
      ctx.strokeStyle = isPlaying ? "rgba(29, 29, 27, 0.07)" : COLORS.railSoft;
      ctx.lineWidth = 1;
      if (!isPlaying) ctx.setLineDash([3, 5]);
      // Left rail
      ctx.beginPath();
      ctx.moveTo(x - a, y - b);
      ctx.lineTo(x - a, y + b);
      ctx.stroke();
      // Right rail
      ctx.beginPath();
      ctx.moveTo(x + a, y - b);
      ctx.lineTo(x + a, y + b);
      ctx.stroke();
      // Top arc
      ctx.beginPath();
      ctx.ellipse(x, y - b, a, a * 0.5, 0, Math.PI, 0);
      ctx.stroke();
      // Bottom arc
      ctx.beginPath();
      ctx.ellipse(x, y + b, a, a * 0.5, 0, 0, Math.PI);
      ctx.stroke();
      ctx.setLineDash([]);

      // Small chain dots along the path
      if (!isPlaying) {
        ctx.fillStyle = "rgba(29, 29, 27, 0.1)";
        for (let j = 0; j < 24; j++) {
          const dotAngle = (j * Math.PI * 2) / 24;
          const dx = x + a * Math.sin(dotAngle);
          const dy = y - b * Math.cos(dotAngle);
          ctx.beginPath();
          ctx.arc(dx, dy, 1.5, 0, Math.PI * 2);
          ctx.fill();
        }
      }
    }

    // Draw each bucket
    for (let i = 0; i < NUM_BUCKETS; i++) {
      const bucketAngle = phase + (i * Math.PI * 2) / NUM_BUCKETS;
      const bx = x + a * Math.sin(bucketAngle);
      const by = y - b * Math.cos(bucketAngle);

      // Piecewise tilt: upright on ascent, tips only at top curve
      const na = ((bucketAngle % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
      const dropDir = part.dropSide === "left" ? -1 : 1;
      const tilt = bucketTilt(na) * dropDir;

      const isAscending = Math.abs(tilt) < Math.PI * 0.3;
      const hasAttachment = meta && meta.bucketAttachments[i];

      ctx.save();
      ctx.translate(bx, by);
      ctx.rotate(tilt);

      const halfW = bw * 0.5;
      const wallH = 16;
      const floorThick = 3;

      // Colors
      const stroke = part.strokeColor || (hasAttachment ? COLORS.pink : (isAscending ? COLORS.pinkRail : COLORS.rail));
      const fillClr = part.fillColor || (hasAttachment
        ? "rgba(218, 22, 122, 0.15)"
        : isAscending
          ? "rgba(218, 22, 122, 0.06)"
          : "rgba(29, 29, 27, 0.03)");

      ctx.lineCap = "round";
      ctx.lineJoin = "round";

      // Bucket body: filled U-shape with thick walls
      // Floor
      ctx.fillStyle = fillClr;
      ctx.beginPath();
      ctx.moveTo(-halfW - 2, floorThick);
      ctx.lineTo(-halfW - 2, -1);
      ctx.quadraticCurveTo(-halfW - 2, -floorThick, -halfW + 4, -floorThick);
      ctx.lineTo(halfW - 4, -floorThick);
      ctx.quadraticCurveTo(halfW + 2, -floorThick, halfW + 2, -1);
      ctx.lineTo(halfW + 2, floorThick);
      ctx.closePath();
      ctx.fill();

      // Left wall
      ctx.strokeStyle = stroke;
      ctx.lineWidth = 2.5;
      ctx.beginPath();
      ctx.moveTo(-halfW, -wallH);
      ctx.lineTo(-halfW, floorThick * 0.5);
      ctx.quadraticCurveTo(-halfW, floorThick + 2, -halfW + 6, floorThick + 2);
      ctx.stroke();

      // Right wall
      ctx.beginPath();
      ctx.moveTo(halfW, -wallH);
      ctx.lineTo(halfW, floorThick * 0.5);
      ctx.quadraticCurveTo(halfW, floorThick + 2, halfW - 6, floorThick + 2);
      ctx.stroke();

      // Floor bottom line
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(-halfW + 4, floorThick + 2);
      ctx.lineTo(halfW - 4, floorThick + 2);
      ctx.stroke();

      // Small pivot dot (chain attachment point)
      ctx.fillStyle = stroke;
      ctx.beginPath();
      ctx.arc(0, -wallH + 2, 2.5, 0, Math.PI * 2);
      ctx.fill();

      ctx.restore();
    }

    ctx.restore();
  }

  function drawSelection() {
    const part = getSelected();
    if (!part) return;
    const bounds = partBounds(part);
    const schema = SCHEMAS[part.type] || [];
    const hasAngle = schema.includes("angle");
    const angle = degToRad(part.angle || 0);
    ctx.save();

    ctx.translate(part.x, part.y);
    ctx.rotate(angle);
    const lx = -bounds.w * 0.5;
    const ly = -bounds.h * 0.5;

    ctx.strokeStyle = COLORS.pink;
    ctx.lineWidth = 1;
    ctx.setLineDash([5, 5]);
    ctx.strokeRect(lx, ly, bounds.w, bounds.h);

    ctx.setLineDash([]);
    ctx.fillStyle = COLORS.pink;
    const hs = 5;
    const corners = [
      [lx, ly],
      [lx + bounds.w, ly],
      [lx, ly + bounds.h],
      [lx + bounds.w, ly + bounds.h],
    ];
    corners.forEach(([cx, cy]) => {
      ctx.fillRect(cx - hs, cy - hs, hs * 2, hs * 2);
    });

    if (hasAngle) {
      ctx.strokeStyle = COLORS.pink;
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(0, ly);
      ctx.lineTo(0, ly - ROTATE_DIST);
      ctx.stroke();
      ctx.beginPath();
      ctx.arc(0, ly - ROTATE_DIST, 5, 0, Math.PI * 2);
      ctx.fill();
    }

    ctx.fillStyle = COLORS.pink;
    ctx.font = "bold 10px Arial, Helvetica, sans-serif";
    ctx.textAlign = "left";
    ctx.textBaseline = "bottom";
    const labelY = hasAngle ? ly - ROTATE_DIST - 10 : ly - 4;
    ctx.fillText(part.type.toUpperCase(), lx, labelY);

    ctx.restore();
  }

  function drawHover() {
    if (!state.hoveredId || state.hoveredId === state.selectedId) return;
    const part = getPart(state.hoveredId);
    if (!part) return;
    const bounds = partBounds(part);
    const angle = degToRad(part.angle || 0);
    ctx.save();
    ctx.translate(part.x, part.y);
    ctx.rotate(angle);
    ctx.strokeStyle = "rgba(218, 22, 122, 0.25)";
    ctx.lineWidth = 1.5;
    ctx.strokeRect(-bounds.w * 0.5, -bounds.h * 0.5, bounds.w, bounds.h);
    ctx.restore();
  }

  function drawPlacementPreview() {
    if (state.mode !== "edit" || state.activeTool === "select" || !state.mousePos) return;
    const px = snapToGrid(state.mousePos.x);
    const py = snapToGrid(state.mousePos.y);
    const previewPart = createPart(state.activeTool, px, py);
    ctx.save();
    ctx.globalAlpha = 0.3;
    drawPart(previewPart, null);
    ctx.restore();
  }

  function onPointerDown(event) {
    const point = canvasPoint(event);
    canvas.setPointerCapture(event.pointerId);

    if (state.mode === "play" || state.mode === "preview") {
      kickAt(point);
      return;
    }

    if (state.activeTool !== "select") {
      const sx = event.shiftKey ? point.x : snapToGrid(point.x);
      const sy = event.shiftKey ? point.y : snapToGrid(point.y);
      const part = createPart(state.activeTool, sx, sy);
      state.scene.parts.push(part);
      state.selectedId = part.id;
      renderInspector();
      return;
    }

    const handle = hitTestHandles(point.x, point.y);
    if (handle) {
      state.drag = { id: state.selectedId, handle };
      canvas.style.cursor = handle === "rotate" ? "grabbing" : "nwse-resize";
      return;
    }

    const hit = hitTest(point.x, point.y);
    state.selectedId = hit?.id || null;
    renderInspector();
    if (hit) {
      state.drag = {
        id: hit.id,
        handle: null,
        dx: point.x - hit.x,
        dy: point.y - hit.y,
      };
    }
  }

  function onPointerMove(event) {
    const point = canvasPoint(event);
    state.mousePos = point;

    if (state.mode !== "edit") return;

    if (!state.drag) {
      const handle = hitTestHandles(point.x, point.y);
      if (handle) {
        const cursorMap = { tl: "nwse-resize", br: "nwse-resize", tr: "nesw-resize", bl: "nesw-resize", rotate: "grab" };
        canvas.style.cursor = cursorMap[handle] || "default";
        state.hoveredId = null;
        return;
      }
      const hit = hitTest(point.x, point.y);
      state.hoveredId = (state.activeTool === "select" && hit) ? hit.id : null;
      canvas.style.cursor = state.activeTool !== "select" ? "copy" : state.hoveredId ? "move" : "default";
      return;
    }

    const part = getPart(state.drag.id);
    if (!part) return;
    const h = state.drag.handle;

    if (h === "rotate") {
      const rawAngle = Math.atan2(point.x - part.x, -(point.y - part.y)) * 180 / Math.PI;
      part.angle = event.shiftKey ? Math.round(rawAngle) : Math.round(rawAngle / 15) * 15;
      renderInspector();
      return;
    }

    if (h === "tl" || h === "tr" || h === "bl" || h === "br") {
      const schema = SCHEMAS[part.type] || [];
      if (schema.includes("r")) {
        const newR = Math.max(8, Math.round(Math.hypot(point.x - part.x, point.y - part.y)));
        part.r = event.shiftKey ? newR : Math.round(newR / SNAP_SIZE) * SNAP_SIZE || SNAP_SIZE;
      } else {
        const angle = degToRad(part.angle || 0);
        const local = rotatePoint(0, 0, point.x - part.x, point.y - part.y, -angle);
        if (schema.includes("w")) {
          const rawW = Math.max(10, Math.round(2 * Math.abs(local.x)));
          part.w = event.shiftKey ? rawW : Math.round(rawW / SNAP_SIZE) * SNAP_SIZE || SNAP_SIZE;
        }
        if (schema.includes("h")) {
          const rawH = Math.max(10, Math.round(2 * Math.abs(local.y)));
          part.h = event.shiftKey ? rawH : Math.round(rawH / SNAP_SIZE) * SNAP_SIZE || SNAP_SIZE;
        }
      }
      renderInspector();
      return;
    }

    const rawX = point.x - state.drag.dx;
    const rawY = point.y - state.drag.dy;
    part.x = event.shiftKey ? Math.round(rawX) : snapToGrid(rawX);
    part.y = event.shiftKey ? Math.round(rawY) : snapToGrid(rawY);
    renderInspector();
  }

  function onPointerUp(event) {
    if (canvas.hasPointerCapture?.(event.pointerId)) {
      canvas.releasePointerCapture(event.pointerId);
    }
    state.drag = null;
    if (event.type === "pointerleave") {
      state.hoveredId = null;
      state.mousePos = null;
      canvas.style.cursor = "default";
    }
  }

  function onKeyDown(event) {
    if (event.target.matches("input, textarea, select")) return;

    if (event.key === "Escape") {
      if (state.mode === "preview") {
        if (!IS_VIEWER) exitPreview();
        return;
      }
      if (state.activeTool !== "select") {
        state.activeTool = "select";
        buildPalette();
        canvas.style.cursor = "default";
      } else {
        state.selectedId = null;
        renderInspector();
      }
      return;
    }

    if (state.mode === "preview") return;

    if (event.key === "Delete" || event.key === "Backspace") deleteSelected();
    if (event.key.toLowerCase() === "p") setMode(state.mode === "play" ? "edit" : "play");
    if (event.key.toLowerCase() === "r" && state.selectedId) {
      const part = getSelected();
      if (part && "angle" in part) {
        part.angle = Math.round((Number(part.angle) || 0) + 15);
        renderInspector();
      }
    }

    const toolIndex = "1234567890".indexOf(event.key);
    if (toolIndex !== -1 && toolIndex < TOOLS.length) {
      state.activeTool = TOOLS[toolIndex].type;
      buildPalette();
    }

    if ((event.key === "d" || event.key === "D") && (event.ctrlKey || event.metaKey)) {
      event.preventDefault();
      const selected = getSelected();
      if (selected) {
        const clone = { ...selected, id: createId(selected.type), x: selected.x + 30, y: selected.y + 30 };
        state.scene.parts.push(clone);
        state.selectedId = clone.id;
        renderInspector();
      }
    }
  }

  function canvasPoint(event) {
    const rect = canvas.getBoundingClientRect();
    return {
      x: (event.clientX - rect.left - state.sceneOffset.x) / state.sceneScale,
      y: (event.clientY - rect.top - state.sceneOffset.y) / state.sceneScale,
    };
  }

  function createPart(type, x, y) {
    const id = createId(type);
    const base = { id, type, x: Math.round(x), y: Math.round(y) };
    return { ...base, ...PART_DEFAULTS[type] };
  }

  function createId(type) {
    return `${type}-${Math.random().toString(36).slice(2, 8)}`;
  }

  const HANDLE_R = 8;
  const ROTATE_DIST = 28;

  function rotatePoint(cx, cy, x, y, angle) {
    const cos = Math.cos(angle);
    const sin = Math.sin(angle);
    const dx = x - cx;
    const dy = y - cy;
    return { x: cx + dx * cos - dy * sin, y: cy + dx * sin + dy * cos };
  }

  function selectionHandles(part) {
    if (!part) return [];
    const bounds = partBounds(part);
    const schema = SCHEMAS[part.type] || [];
    const angle = degToRad(part.angle || 0);
    const hw = bounds.w * 0.5;
    const hh = bounds.h * 0.5;
    const localCorners = [
      { id: "tl", lx: -hw, ly: -hh },
      { id: "tr", lx: hw, ly: -hh },
      { id: "bl", lx: -hw, ly: hh },
      { id: "br", lx: hw, ly: hh },
    ];
    const handles = localCorners.map((c) => {
      const p = rotatePoint(0, 0, c.lx, c.ly, angle);
      return { id: c.id, x: part.x + p.x, y: part.y + p.y };
    });
    if (schema.includes("angle")) {
      const p = rotatePoint(0, 0, 0, -hh - ROTATE_DIST, angle);
      handles.push({ id: "rotate", x: part.x + p.x, y: part.y + p.y });
    }
    return handles;
  }

  function hitTestHandles(x, y) {
    const part = getSelected();
    if (!part || state.activeTool !== "select") return null;
    const handles = selectionHandles(part);
    for (const h of handles) {
      if (Math.hypot(x - h.x, y - h.y) <= HANDLE_R) return h.id;
    }
    return null;
  }

  function hitTest(x, y) {
    for (let i = state.scene.parts.length - 1; i >= 0; i -= 1) {
      const part = state.scene.parts[i];
      if (containsPoint(part, x, y)) return part;
    }
    return null;
  }

  function containsPoint(part, x, y) {
    if (part.type === "pill" || part.type === "gear" || part.type === "trigger") {
      const r = part.r || 40;
      return Math.hypot(x - part.x, y - part.y) <= r + 10;
    }
    if (part.type === "bucketLift") {
      const a = (Number(part.w) || 60) + (Number(part.bucketW) || 36) * 0.5 + 4;
      const bh = (Number(part.h) || 200) + 4;
      return Math.abs(x - part.x) <= a && Math.abs(y - part.y) <= bh;
    }
    const bounds = partBounds(part);
    const angle = degToRad(part.angle || 0);
    const local = rotatePoint(0, 0, x - part.x, y - part.y, -angle);
    const hw = bounds.w * 0.5 + 4;
    const hh = bounds.h * 0.5 + 4;
    return local.x >= -hw && local.x <= hw && local.y >= -hh && local.y <= hh;
  }

  function partBounds(part) {
    if (part.type === "pill" || part.type === "gear" || part.type === "trigger") {
      const r = part.r || 40;
      return { x: part.x - r, y: part.y - r, w: r * 2, h: r * 2 };
    }
    if (part.type === "bucketLift") {
      const a = (Number(part.w) || 60) + (Number(part.bucketW) || 36) * 0.5;
      const bh = Number(part.h) || 200;
      return { x: part.x - a, y: part.y - bh, w: a * 2, h: bh * 2 };
    }
    if (part.type === "catapult") {
      return { x: part.x - part.w * 0.35, y: part.y - 30, w: part.w, h: 60 };
    }
    return { x: part.x - (part.w || 40) * 0.5, y: part.y - (part.h || 40) * 0.5, w: part.w || 40, h: part.h || 40 };
  }

  function renderInspector() {
    if (!inspector) return;
    const part = getSelected();
    selectedType.textContent = part ? part.type.charAt(0).toUpperCase() + part.type.slice(1) : "Scene";
    inspector.innerHTML = "";

    if (!part) {
      addReadonly("Parts", String(state.scene.parts.length));
      addReadonly("Mode", state.mode);
      addGroupLabel("Curtain Colors");
      const colors = sceneCurtainColors();
      colors.forEach((rgba, i) => {
        const parsed = parseRgba(rgba);
        const row = document.createElement("div");
        row.className = "field";
        row.style.cssText = "grid-template-columns:32px minmax(0,1fr) auto;gap:4px";

        const swatch = document.createElement("input");
        swatch.type = "color";
        swatch.value = parsed.hex;
        swatch.style.cssText = "width:32px;height:24px;padding:0;border:1px solid rgba(0,0,0,0.15);border-radius:3px;cursor:pointer;background:none";
        swatch.addEventListener("input", () => {
          const arr = ensureSceneCurtainColors();
          arr[i] = toRgba(swatch.value, Number(opac.value) / 100);
          curtainQueue = [];
        });

        const opac = document.createElement("input");
        opac.type = "number";
        opac.min = "1";
        opac.max = "100";
        opac.step = "1";
        opac.value = Math.round(parsed.alpha * 100);
        opac.title = "Opacity %";
        opac.style.cssText = "width:100%;min-width:0;height:24px;border:1px solid rgba(0,0,0,0.15);padding:0 4px;font-size:11px";
        opac.addEventListener("input", () => {
          const arr = ensureSceneCurtainColors();
          arr[i] = toRgba(swatch.value, clamp(Number(opac.value), 1, 100) / 100);
          curtainQueue = [];
        });

        const del = document.createElement("span");
        del.textContent = "\u00d7";
        del.style.cssText = "cursor:pointer;opacity:0.4;font-size:16px;line-height:1;user-select:none;padding:0 2px";
        del.addEventListener("click", () => {
          const arr = ensureSceneCurtainColors();
          if (arr.length <= 1) return;
          arr.splice(i, 1);
          curtainQueue = [];
          renderInspector();
        });

        row.append(swatch, opac, del);
        inspector.appendChild(row);
      });

      const addBtn = document.createElement("button");
      addBtn.className = "control";
      addBtn.textContent = "+ Add Color";
      addBtn.style.cssText = "width:100%;min-height:26px;font-size:12px;margin-top:4px";
      addBtn.addEventListener("click", () => {
        const arr = ensureSceneCurtainColors();
        arr.push("rgba(160, 160, 160, 0.25)");
        curtainQueue = [];
        renderInspector();
      });
      inspector.appendChild(addBtn);
      return;
    }

    const schema = SCHEMAS[part.type] || [];

    addReadonly("ID", part.id);

    const posFields = ["x", "y"];
    const dimFields = ["w", "h", "r", "angle", "bucketW", "buckets"];
    const behaviorFields = ["density", "restitution", "friction", "frictionAir", "inertia", "speed", "power", "travel", "mode", "startPos", "triggerAction", "driveMode", "flip", "dropSide", "preloaded", "pillR", "pillBounce", "pillDensity", "pillFriction", "pillAir", "pillInertia"];

    const hasPos = posFields.some((k) => schema.includes(k));
    const hasDim = dimFields.some((k) => schema.includes(k));
    const hasBehavior = behaviorFields.some((k) => schema.includes(k));

    if (hasPos) {
      addGroupLabel("Position");
      posFields.forEach((key) => { if (schema.includes(key)) addField(part, key); });
    }
    if (hasDim) {
      addGroupLabel("Dimensions");
      dimFields.forEach((key) => { if (schema.includes(key)) addField(part, key); });
    }
    if (hasBehavior) {
      addGroupLabel("Behavior");
      behaviorFields.forEach((key) => {
        if (!schema.includes(key)) return;
        if (key === "triggerAction" && part.type === "elevator" && (part.mode || "continuous") !== "trigger") return;
        if (key === "speed" && part.type === "gear" && (part.driveMode || "motor") === "free") return;
        if (key.startsWith("pill") && part.type === "bucketLift" && part.preloaded !== "yes") return;
        addField(part, key);
      });
    }

    const styleFields = ["strokeColor", "fillColor", "showGuides"];
    const hasStyle = styleFields.some((k) => schema.includes(k));
    if (hasStyle) {
      addGroupLabel("Style");
      styleFields.forEach((key) => { if (schema.includes(key)) addField(part, key); });
    }
  }

  function addGroupLabel(text) {
    const el = document.createElement("div");
    el.className = "field-group-label";
    el.textContent = text;
    inspector.appendChild(el);
  }

  function addField(part, key) {
    const row = document.createElement("div");
    row.className = "field";
    const label = document.createElement("label");
    label.textContent = LABELS[key] || key;

    if (COLOR_FIELDS.has(key)) {
      const wrapper = document.createElement("div");
      wrapper.style.cssText = "display:flex;align-items:center;gap:4px";
      const input = document.createElement("input");
      input.type = "color";
      input.value = part[key] || "#da167a";
      input.style.cssText = "width:32px;height:24px;padding:0;border:1px solid rgba(0,0,0,0.15);border-radius:3px;cursor:pointer;background:none";
      input.addEventListener("input", () => {
        part[key] = input.value;
      });
      wrapper.appendChild(input);
      if (part[key]) {
        const reset = document.createElement("span");
        reset.textContent = "\u00d7";
        reset.title = "Reset to default";
        reset.style.cssText = "cursor:pointer;opacity:0.4;font-size:14px;line-height:1;user-select:none";
        reset.addEventListener("click", () => {
          delete part[key];
          renderInspector();
        });
        wrapper.appendChild(reset);
      }
      row.append(label, wrapper);
    } else if (SELECT_OPTIONS[key]) {
      const select = document.createElement("select");
      const options = SELECT_OPTIONS[key];
      const current = part[key] || options[0].value;
      options.forEach((opt) => {
        const option = document.createElement("option");
        option.value = opt.value;
        option.textContent = opt.label;
        if (current === opt.value) option.selected = true;
        select.appendChild(option);
      });
      select.addEventListener("change", () => {
        part[key] = select.value;
        if (state.mode === "play" || state.mode === "preview") buildWorld();
        renderInspector();
      });
      row.append(label, select);
    } else {
      const input = document.createElement("input");
      input.type = "number";
      const fineStep = (key === "density" || key === "power" || key === "restitution" || key === "pillBounce" ||
        key === "friction" || key === "frictionAir" || key === "inertia" ||
        key === "pillDensity" || key === "pillFriction" || key === "pillAir" || key === "pillInertia") ||
        (key === "speed" && part.type !== "bucketLift");
      input.step = fineStep ? "0.001" : "1";
      const def = PART_DEFAULTS[part.type];
      input.value = part[key] ?? (def && def[key]) ?? "";
      input.addEventListener("input", () => {
        part[key] = Number(input.value);
        if (state.mode === "play" || state.mode === "preview") buildWorld();
      });
      row.append(label, input);
    }

    inspector.appendChild(row);
  }

  function addReadonly(labelText, value) {
    const row = document.createElement("div");
    row.className = "field";
    const label = document.createElement("label");
    label.textContent = labelText;
    const input = document.createElement("input");
    input.value = value;
    input.readOnly = true;
    row.append(label, input);
    inspector.appendChild(row);
  }

  // ── Level slot management ──────────────────────────────

  function loadSlots() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    } catch { return []; }
  }

  function saveSlots(slots) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(slots));
  }

  function saveToNewSlot() {
    const slots = loadSlots();
    const num = slots.length + 1;
    const slot = {
      id: Date.now().toString(36),
      name: "Level " + num,
      scene: JSON.parse(JSON.stringify(state.scene)),
      updatedAt: Date.now(),
    };
    slots.unshift(slot);
    saveSlots(slots);
    state.currentSlotId = slot.id;
    renderLevelList();
  }

  function updateSlot(id) {
    const slots = loadSlots();
    const slot = slots.find((s) => s.id === id);
    if (!slot) return;
    slot.scene = JSON.parse(JSON.stringify(state.scene));
    slot.updatedAt = Date.now();
    saveSlots(slots);
    renderLevelList();
  }

  function loadFromSlot(id) {
    const slots = loadSlots();
    const slot = slots.find((s) => s.id === id);
    if (!slot) return;
    state.scene = normalizeScene(slot.scene);
    state.selectedId = null;
    state.currentSlotId = id;
    setMode("edit");
    renderInspector();
    renderLevelList();
  }

  function loadBlankLevel() {
    state.scene = blankScene();
    state.selectedId = null;
    state.currentSlotId = BUILTIN_BLANK_ID;
    setMode("edit");
    renderInspector();
    renderLevelList();
  }

  function loadDemoLevel() {
    state.scene = defaultScene();
    state.selectedId = null;
    state.currentSlotId = BUILTIN_DEMO_ID;
    setMode("edit");
    renderInspector();
    renderLevelList();
  }

  function deleteSlot(id) {
    let slots = loadSlots();
    slots = slots.filter((s) => s.id !== id);
    saveSlots(slots);
    if (state.currentSlotId === id) state.currentSlotId = null;
    renderLevelList();
  }

  function renameSlot(id, newName) {
    const slots = loadSlots();
    const slot = slots.find((s) => s.id === id);
    if (!slot) return;
    slot.name = newName || slot.name;
    saveSlots(slots);
    renderLevelList();
  }

  function renderLevelList() {
    if (!levelListEl) return;
    levelListEl.innerHTML = "";
    levelListEl.appendChild(createLevelRow({
      id: BUILTIN_BLANK_ID,
      name: BUILTIN_BLANK_NAME,
      builtin: true,
    }));
    levelListEl.appendChild(createLevelRow({
      id: BUILTIN_DEMO_ID,
      name: BUILTIN_DEMO_NAME,
      builtin: true,
    }));
    const slots = loadSlots();
    if (slots.length === 0) {
      const empty = document.createElement("div");
      empty.style.cssText = "font-size:11px;color:var(--muted);padding:6px 0;";
      empty.textContent = "No saved levels";
      levelListEl.appendChild(empty);
      return;
    }
    slots.forEach((slot) => {
      levelListEl.appendChild(createLevelRow(slot));
    });
  }

  function createLevelRow(slot) {
    const row = document.createElement("div");
    row.className = "level-item" + (slot.id === state.currentSlotId ? " is-active" : "");

    const name = document.createElement("span");
    name.className = "level-name";
    name.textContent = slot.name;
    if (!slot.builtin) {
      name.title = "Double-click to rename";
      name.addEventListener("dblclick", () => startRename(row, slot));
    }

    const loadBtn = document.createElement("button");
    loadBtn.className = "level-btn";
    loadBtn.title = "Load";
    loadBtn.textContent = "↵";
    loadBtn.addEventListener("click", () => {
      if (slot.id === BUILTIN_BLANK_ID) loadBlankLevel();
      else if (slot.id === BUILTIN_DEMO_ID) loadDemoLevel();
      else loadFromSlot(slot.id);
    });

    if (slot.builtin) {
      row.append(name, loadBtn);
      row.style.gridTemplateColumns = "minmax(0,1fr) auto";
      return row;
    }

    const delBtn = document.createElement("button");
    delBtn.className = "level-btn";
    delBtn.title = "Delete";
    delBtn.textContent = "×";
    delBtn.addEventListener("click", () => deleteSlot(slot.id));

    row.append(name, loadBtn, delBtn);

    if (slot.id === state.currentSlotId) {
      const saveBtn = document.createElement("button");
      saveBtn.className = "level-btn";
      saveBtn.title = "Overwrite";
      saveBtn.textContent = "✓";
      saveBtn.addEventListener("click", () => updateSlot(slot.id));
      row.insertBefore(saveBtn, loadBtn);
      row.style.gridTemplateColumns = "minmax(0,1fr) auto auto auto";
    }

    return row;
  }

  function startRename(row, slot) {
    const nameSpan = row.querySelector(".level-name");
    if (!nameSpan) return;
    const input = document.createElement("input");
    input.className = "level-name-input";
    input.value = slot.name;
    nameSpan.replaceWith(input);
    input.focus();
    input.select();
    const finish = () => {
      const val = input.value.trim();
      if (val && val !== slot.name) renameSlot(slot.id, val);
      else renderLevelList();
    };
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); finish(); }
      if (e.key === "Escape") renderLevelList();
    });
    input.addEventListener("blur", finish);
  }

  function downloadLevel() {
    const slots = loadSlots();
    const current = slots.find((s) => s.id === state.currentSlotId);
    const filename = (current ? current.name.replace(/[^a-zA-Z0-9_-]/g, "_") : "bespoke-level") + ".json";
    const blob = new Blob([JSON.stringify(state.scene, null, 2)], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }

  function uploadLevel() {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".json";
    input.addEventListener("change", () => {
      const file = input.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        try {
          const data = JSON.parse(reader.result);
          if (!Array.isArray(data.parts)) return;
          state.scene = normalizeScene(data);
          state.selectedId = null;
          state.currentSlotId = null;
          setMode("edit");
          renderInspector();
          renderLevelList();
        } catch { /* invalid JSON */ }
      };
      reader.readAsText(file);
    });
    input.click();
  }

  function migrateOldStorage() {
    const old = localStorage.getItem(STORAGE_KEY_OLD);
    if (!old) return;
    try {
      const scene = JSON.parse(old);
      if (!Array.isArray(scene.parts)) return;
      const slots = loadSlots();
      slots.push({
        id: Date.now().toString(36),
        name: "Imported",
        scene,
        updatedAt: Date.now(),
      });
      saveSlots(slots);
      localStorage.removeItem(STORAGE_KEY_OLD);
    } catch { /* ignore bad data */ }
  }

  function deleteSelected() {
    if (!state.selectedId) return;
    state.scene.parts = state.scene.parts.filter((part) => part.id !== state.selectedId);
    state.selectedId = null;
    if (state.mode === "play" || state.mode === "preview") buildWorld();
    renderInspector();
  }

  function getSelected() {
    return getPart(state.selectedId);
  }

  function getPart(id) {
    return state.scene.parts.find((part) => part.id === id);
  }

  function kickAt(point) {
    if (!state.world) return;
    state.bodiesById.forEach((body) => {
      if (!isDynamicBody(body)) return;
      const delta = Vector.sub(body.position, point);
      const distance = Math.max(35, Vector.magnitude(delta));
      if (distance > 210) return;
      const force = Vector.mult(Vector.normalise(delta), (1 - distance / 210) * 0.045);
      Body.applyForce(body, body.position, force);
    });
  }

  function catapultCup(part, body, dir) {
    const d = dir !== undefined ? dir : (part.flip === "yes" ? -1 : 1);
    return Vector.add(body.position, Vector.rotate({ x: part.w * 0.5 * d, y: 0 }, body.angle));
  }

  function catapultCupRadius(part) {
    return clamp(part.w * 0.24, 34, 52);
  }

  function roundRect(context, x, y, width, height, radius) {
    const r = Math.min(radius, width / 2, height / 2);
    context.beginPath();
    context.moveTo(x + r, y);
    context.arcTo(x + width, y, x + width, y + height, r);
    context.arcTo(x + width, y + height, x, y + height, r);
    context.arcTo(x, y + height, x, y, r);
    context.arcTo(x, y, x + width, y, r);
    context.closePath();
  }

  window.advanceTime = (ms = FRAME_STEP) => {
    const steps = Math.max(1, Math.round(ms / FRAME_STEP));
    for (let i = 0; i < steps; i += 1) {
      state.time += 1;
      update(FRAME_STEP);
    }
    draw();
    return Promise.resolve();
  };

  window.render_game_to_text = () => {
    const logo = state.logoLayout ? {
      centerX: Math.round(state.logoLayout.logoX + state.logoLayout.logoWidth * 0.5),
      centerY: Math.round(state.logoLayout.logoY + state.logoLayout.logoHeight * 0.5),
      canvasCenterX: Math.round(refWidth() * 0.5),
      canvasCenterY: Math.round(refHeight() * 0.5),
    } : null;
    const parts = state.scene.parts.map((part) => {
      const body = state.bodiesById.get(part.id);
      const pos = body ? body.position : simulatedPart(part);
      return {
        id: part.id,
        type: part.type,
        x: Math.round(pos.x),
        y: Math.round(pos.y),
        speed: body ? Number(Vector.magnitude(body.velocity).toFixed(2)) : 0,
      };
    });
    return JSON.stringify({
      mode: state.mode,
      coordinateSystem: "origin top-left, x right, y down",
      canvas: { width: Math.round(refWidth()), height: Math.round(refHeight()) },
      sceneScale: Number(state.sceneScale.toFixed(3)),
      panels: {
        leftCollapsed: state.panels.leftCollapsed,
        rightCollapsed: state.panels.rightCollapsed,
      },
      logo,
      parts,
    });
  };
})();

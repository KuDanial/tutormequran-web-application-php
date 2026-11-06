// Optional presentation only; PHP validates the proposal with or without JavaScript.
(() => {
  const mode = document.getElementById('change-mode');
  const include = document.querySelector('[name="include_arrangement"]');
  if (!mode || !include) return;
  const update = () => {
    document.querySelectorAll('[data-arrangement-mode]').forEach(group => {
      const active = include.checked && group.dataset.arrangementMode === mode.value;
      group.hidden = !active;
      group.querySelectorAll('input, textarea').forEach(input => {
        input.disabled = !active;
        input.required = active;
      });
    });
  };
  mode.addEventListener('change', update);
  include.addEventListener('change', update);
  update();
})();

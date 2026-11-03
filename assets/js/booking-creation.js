(() => {
  const form = document.querySelector('[data-booking-create]');
  if (!form) return;
  const update = () => {
    const slot = form.querySelector('[name="slotId"]:checked');
    if (!slot) return;
    const modes = slot.dataset.modes.split(',');
    for (const option of form.elements.classType.options) option.disabled = !modes.includes(option.value);
    if (!modes.includes(form.elements.classType.value)) form.elements.classType.value = modes[0];
    document.querySelector('[data-booking-duration]').textContent = `${slot.dataset.hours} hour(s)`;
    document.querySelector('[data-booking-fee]').textContent = `RM ${slot.dataset.amount}`;
  };
  form.addEventListener('change', update);
  update();
})();

import React from 'react';
import './ToggleSwitch.css'; // Importa os estilos que acabamos de criar

// O componente recebe 3 props:
// - label: O texto que aparecerá ao lado do switch.
// - checked: Um booleano (true ou false) que define se o switch está ligado.
// - onChange: A função que será chamada quando o switch for clicado.
const ToggleSwitch = ({ label, checked, onChange }) => {
  return (
    <label className="toggle-switch-container" htmlFor={label}>
      <div className="toggle-switch">
        <input
          id={label}
          type="checkbox"
          checked={checked}
          onChange={onChange}
        />
        <span className="slider" />
      </div>
      <span className="toggle-label">{label}</span>
    </label>
  );
};

export default ToggleSwitch;

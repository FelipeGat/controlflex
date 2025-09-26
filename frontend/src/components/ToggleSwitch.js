import React from 'react';
import './ToggleSwitch.css';


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

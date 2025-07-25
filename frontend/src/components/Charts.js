import React from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js';
import { Bar, Line, Pie, Doughnut } from 'react-chartjs-2';

ChartJS.register(
  CategoryScale, LinearScale, BarElement,
  PointElement, LineElement, ArcElement,
  Tooltip, Legend
);

export const ChartRenderer = ({ type, data, options }) => {
  const ChartComponent = { bar: Bar, line: Line, pie: Pie, doughnut: Doughnut }[type];
  return <ChartComponent data={data} options={options} />;
};

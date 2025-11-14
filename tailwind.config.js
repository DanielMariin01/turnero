/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
    "./resources/**/*.vue",
  ],
  theme: {
  extend: {
      colors: {
        color: { // 👈 Nombre de tu paleta
          '50': '#E0F7F7',
          '100': '#B3EAEA',
          '200': '#80DDDD',
          '300': '#4DCFCF',
          '400': '#26C2C2',
          '500': '#00B5B5', // principal
          '600': '#009E9E',
          '700': '#008787',
          '800': '#006F6F',
          '900': '#005858',
        },
      },
    },
  },
  plugins: [],
}


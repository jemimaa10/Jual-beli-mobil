/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        // Merah khas Auto18 (logo "18" & garis diagonal poster)
        brand: {
          50: '#fff1f1',
          100: '#ffdfdf',
          200: '#ffc5c5',
          300: '#ff9d9d',
          400: '#ff6464',
          500: '#ff3b3b',
          600: '#e41b1b',
          700: '#c01313',
          800: '#9e1414',
          900: '#831818',
          950: '#480707',
        },
        // Aspal: latar & permukaan gaya Motorsport
        ink: {
          DEFAULT: '#070708',
          900: '#0d0d0f',
          800: '#151518',
          700: '#1e1e22',
          600: '#2a2a30',
          500: '#3a3a42',
        },
      },
      fontFamily: {
        sans: ['Barlow', 'system-ui', 'sans-serif'],
        display: ['"Barlow Condensed"', 'Barlow', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
}

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
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
          500: '#f83b3b',
          600: '#e41b1b',
          700: '#c01313',
          800: '#9e1414',
          900: '#831818',
          950: '#480707',
        },
        // Hitam pekat latar poster Auto18
        ink: {
          DEFAULT: '#070707',
          900: '#0b0b0b',
          800: '#121212',
          700: '#1a1a1a',
          600: '#242424',
        },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        display: ['Montserrat', '"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
}

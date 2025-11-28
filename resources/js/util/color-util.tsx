const ColorUtil = {
  getRandomHexColor: function () {
    // Generate random values for red, green, and blue components
    const randomRed = Math.floor(Math.random() * 256);
    const randomGreen = Math.floor(Math.random() * 256);
    const randomBlue = Math.floor(Math.random() * 256);

    // Convert the RGB values to a hex string
    const redHex = randomRed.toString(16).padStart(2, '0');
    const greenHex = randomGreen.toString(16).padStart(2, '0');
    const blueHex = randomBlue.toString(16).padStart(2, '0');

    // Combine the hex values to create the final color
    const hexColor = `#${redHex}${greenHex}${blueHex}`;

    return hexColor;
  },

  getContrastingColor: function (hexColor: string) {
    // Remove the "#" character if it's included
    hexColor = hexColor.replace(/^#/, '');

    // Parse the hex color to get the red, green, and blue components
    const r = parseInt(hexColor.slice(0, 2), 16);
    const g = parseInt(hexColor.slice(2, 4), 16);
    const b = parseInt(hexColor.slice(4, 6), 16);

    // Calculate the luminance of the color
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Choose white or black as the contrasting color based on the luminance
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
  },
};

export default ColorUtil;

export const ThemeColors = {
  edumanager: {
    main: '#2a8864',
    50: '#ecf9f4',
    100: '#c5eddd',
    200: '#9ee1c7',
    300: '#77d5b1',
    400: '#50c99a',
    500: '#36af81',
    600: '#2a8864',
    700: '#1e6148',
    800: '#123a2b',
    900: '#06130e',
  },
  red: {
    main: '#B91C1C',
    50: '#FEE2E2',
    100: '#FECACA',
    200: '#FCA5A5',
    300: '#F87171',
    400: '#EF4444',
    500: '#DC2626',
    600: '#B91C1C',
    700: '#991B1B',
    800: '#7F1D1D',
    900: '#631717',
  },
  orange: {
    main: '#EA580C',
    50: '#FFF7ED',
    100: '#FFEDD5',
    200: '#FED7AA',
    300: '#FDBA74',
    400: '#FB923C',
    500: '#F97316',
    600: '#EA580C',
    700: '#C2410C',
    800: '#9A3412',
    900: '#7C2D12',
  },
  yellow: {
    main: '#CA8A04',
    50: '#FEFCE8',
    100: '#FEF9C3',
    200: '#FEF08A',
    300: '#FDE047',
    400: '#FACC15',
    500: '#EAB308',
    600: '#CA8A04',
    700: '#A16207',
    800: '#854D0E',
    900: '#713F12',
  },
  green: {
    main: '#22C55E',
    50: '#F0FDF4',
    100: '#DCFCE7',
    200: '#BBF7D0',
    300: '#86EFAC',
    400: '#4ADE80',
    500: '#22C55E',
    600: '#16A34A',
    700: '#15803D',
    800: '#166534',
    900: '#14532D',
  },
  blue: {
    main: '#2563EB',
    50: '#EFF6FF',
    100: '#DBEAFE',
    200: '#BFDBFE',
    300: '#93C5FD',
    400: '#60A5FA',
    500: '#3B82F6',
    600: '#2563EB',
    700: '#1D4ED8',
    800: '#1E40AF',
    900: '#1E3A8A',
  },
  purple: {
    main: '#7C3AED',
    50: '#F5F3FF',
    100: '#EDE9FE',
    200: '#DDD6FE',
    300: '#C4B5FD',
    400: '#A78BFA',
    500: '#8B5CF6',
    600: '#7C3AED',
    700: '#6D28D9',
    800: '#5B21B6',
    900: '#4C1D95',
  },
  pink: {
    main: '#DB2777',
    50: '#FDF2F8',
    100: '#FCE7F3',
    200: '#FBCFE8',
    300: '#F9A8D4',
    400: '#F472B6',
    500: '#EC4899',
    600: '#DB2777',
    700: '#BE185D',
    800: '#9D174D',
    900: '#831843',
  },
} as Record<BrandColor, Record<string, string>>;

export enum BrandColor {
  Edumanager = 'edumanager',
  Red = 'red',
  Orange = 'orange',
  Yellow = 'yellow',
  Green = 'green',
  Blue = 'blue',
  Purple = 'purple',
  Pink = 'pink',
}

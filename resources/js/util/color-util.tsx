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

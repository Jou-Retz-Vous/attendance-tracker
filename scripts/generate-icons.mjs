import sharp from 'sharp';

await sharp('public/assets/icon.svg')
    .resize(180, 180)
    .png()
    .toFile('public/assets/apple-touch-icon.png');

console.log('Generated apple-touch-icon.png (180×180)');

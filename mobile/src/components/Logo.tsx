import { Image, StyleSheet } from 'react-native';

type LogoProps = {
  size?: 'small' | 'medium' | 'large';
};

export function Logo({ size = 'medium' }: LogoProps) {
  return (
    <Image
      source={require('../../assets/images/reapersventory-logo.png')}
      style={[styles.image, styles[size]]}
      resizeMode="contain"
      accessibilityLabel="Reapersventory logo"
    />
  );
}

const styles = StyleSheet.create({
  image: {
    alignSelf: 'center',
  },
  small: {
    width: 72,
    height: 72,
  },
  medium: {
    width: 140,
    height: 140,
  },
  large: {
    width: 200,
    height: 200,
  },
});

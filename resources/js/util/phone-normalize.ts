const PhoneNumberNormalizer = {
  normalize: (phone?: string | null): string | null => {
    let normalizedPhone = PhoneNumberNormalizer.digits(phone);

    if (!normalizedPhone) {
      return null;
    }

    if (normalizedPhone.startsWith('00')) {
      normalizedPhone = normalizedPhone.slice(2);
    }

    if (normalizedPhone.startsWith('0') && normalizedPhone.length === 11) {
      return `234${normalizedPhone.slice(1)}`;
    }

    if (normalizedPhone.length === 10 && normalizedPhone.startsWith('8')) {
      return `234${normalizedPhone}`;
    }

    return normalizedPhone;
  },

  lookupVariants: (phone?: string | null): string[] => {
    const normalized = PhoneNumberNormalizer.normalize(phone);

    if (!normalized) {
      return [];
    }

    const variants: string[] = [normalized];

    if (normalized.startsWith('234') && normalized.length === 13) {
      const local = normalized.slice(3);

      variants.push(`0${local}`);
      variants.push(local);
    }

    return [...new Set(variants.filter(Boolean))];
  },

  digits: (phone?: string | null): string | null => {
    const digits = (phone ?? '').replace(/\D+/g, '');

    return digits.length > 0 ? digits : null;
  },
};

export default PhoneNumberNormalizer;

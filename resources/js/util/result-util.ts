const ResultUtil = {
  getPositionSuffix: function (position: number) {
    const lastChar = position % 10;
    let suffix = '';
    switch (lastChar) {
      case 1:
        suffix = 'st';
      case 2:
        suffix = 'nd';
      case 3:
        suffix = 'rd';
      default:
        suffix = 'th';
    }
    return position + suffix;
  },

  getRemark: function (grade: string) {
    switch (grade) {
      case 'A':
        return 'Excellent';
      case 'B':
        return 'Very Good';
      case 'C':
        return 'Good';
      case 'D':
        return 'Fair';
      case 'E':
        return 'Poor';
      case 'F':
        return 'Failed';
      default:
        return 'Unknown';
    }
  },

  getClassSection: function (classTitle: string) {
    classTitle = classTitle.toLowerCase().replaceAll(' ', '');
    if (classTitle.indexOf('ss') >= 0 || classTitle.indexOf('ss') >= 0) {
      return 'Senior Secondary Section';
    } else if (
      classTitle.indexOf('js') >= 0 ||
      classTitle.indexOf('j.s') >= 0
    ) {
      return 'Junior Secondary Section';
    } else if (classTitle.indexOf('primary')) {
      return 'Primary Section';
    } else {
      return 'School Section';
    }
  },
};

export default ResultUtil;

const ObjectUtil = {
  get: (data: any, path: string) => {
    const paths = path.split('.');
    let baseData = { ...data };
    paths.forEach((p) => {
      baseData = baseData[p] as any;
    });
    return baseData as any;
  },

  set: (data: any, path: string, value: any) => {
    const paths = path.split('.');
    const baseData = { ...data };
    let dataToSet = baseData;
    paths.forEach((p) => {
      dataToSet = dataToSet[p] as any;
    });
    dataToSet = value;
    return baseData;
  },
};

export default ObjectUtil;

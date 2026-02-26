// These Types are used for the iFrame body class insertion.
declare global {
  interface Window {
    iframeBodyData?: {
      classes: string;
    };
  }

  const iframeBodyData: {
    classes: string;
  } | undefined;
}

export {};

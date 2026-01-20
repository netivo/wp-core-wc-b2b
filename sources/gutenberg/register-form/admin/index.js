import { registerBlockType } from "@wordpress/blocks";

import Edit from "./edit";

registerBlockType("netivo/contact-form", {
  edit: Edit,
  save: () => {
    return null;
  },
});

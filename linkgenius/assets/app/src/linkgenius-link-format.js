import { __ } from "@wordpress/i18n";
import { ToolbarGroup, ToolbarButton } from "@wordpress/components";
import { Fragment, useState } from "@wordpress/element";
import {
  applyFormat,
  removeFormat,
  registerFormatType,
  useAnchor
} from "@wordpress/rich-text";
import {
  BlockControls, URLPopover,
} from "@wordpress/block-editor";
import { linkgenius_icon } from "./linkgenius-icon";
import { LinkSelector } from "./linkgenius-link-selector";

const linkgenius_type = 'linkgenius/link'
    
registerFormatType( linkgenius_type, {
    title: __('LinkGenius Link', 'linkgenius'),
    className: 'linkgenius-link',
    tagName: "linkgenius-link",
    attributes: {
      linkgenius_id: "linkgenius_id"
    },
    edit: (props) => {
      const { isActive, value, onChange, activeAttributes, contentRef } = props
      const [rect, setRect] = useState(null);
    
      const addLink = (linkgenius_id="") => {
        onChange(applyFormat(value, {
          type: linkgenius_type,
          attributes: { linkgenius_id: (linkgenius_id+"") }
        }));
      }
    
      const removeLink = () => {
        onChange(removeFormat(value, linkgenius_type));
      }
    
      let anchor = useAnchor({ 
        editableContentElement: contentRef.current,
        value });
      
      // hacky way to get the popover to show up in the right place since anchor turns out to be all 0s when clicking
      if(anchor !== null && anchor !== undefined && anchor.getBoundingClientRect().width !== 0 &&
        (rect === null || 
          (anchor.getBoundingClientRect().x !== rect.x ||
          anchor.getBoundingClientRect().y !== rect.y ||
          anchor.getBoundingClientRect().width !== rect.width ||
          anchor.getBoundingClientRect().height !== rect.height))
          ) {
        setRect(anchor.getBoundingClientRect());
      }
      anchor = {
        getBoundingClientRect: () => rect 
      }
      return (
        <Fragment>
          {isActive && (
            <BlockControls>
              <ToolbarGroup>
                <ToolbarButton
                  icon="editor-unlink"
                  title={__('Remove All Affiliate Link', 'linkgenius')}
                  onClick={removeLink}
                  isActive={isActive}
                />
              </ToolbarGroup>
            </BlockControls>
          )}
          {!isActive && (
            <BlockControls>
              <ToolbarGroup>
                <ToolbarButton
                  icon={linkgenius_icon}
                  title={__('All Affiliate Link', 'linkgenius')}
                  onClick={() => addLink("")}
                />
              </ToolbarGroup>
            </BlockControls>
          )}
          {(isActive) && (
            <URLPopover anchor={anchor} autoFocus={true}>
              <LinkSelector
                link_id={activeAttributes.linkgenius_id}
                onChange={(id, link) => {
                  addLink(id)}
                }
                placement="right"
                anchor />
            </URLPopover>
          )}
        </Fragment>
      );
    }
} );
<?php
/**
 * Payments Module
 *
 * @package modules
 * @subpackage payments
 * @category Third Party Xaraya Module
 * @version 1.0.0
 * @copyright (C) 2016 Luetolf-Carroll GmbH
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
/**
 *
 * Initialise or remove the payments module
 *
 */

    function payments_init()
    {

    # --------------------------------------------------------
    #
    # Create the database tables
    #
        sys::import('xaraya.structures.query');
        $q = new Query();
        $prefix = xarDB::getPrefix();

        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_gateways";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_gateways (
            id integer unsigned NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            description text,
            class varchar(255) default NULL,
            classpath varchar(255) default NULL,
            state int default 1,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;

        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_paymentmethods";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_paymentmethods (
            id integer unsigned NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            category int default NULL,
            description text,
            icon varchar(255) default NULL,
            regex varchar(255) default NULL,
            controlnumber int default NULL,
            state int default 1,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;

        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_ccpayments";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_ccpayments (
            id integer unsigned NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            cc_type int NULL,
            number varchar(255) NULL,
            expiration int NULL,
            controlnumber int default NULL,
            order_reference int default NULL,
            time_created int default '0',
            time_processed int default '0',
            state int default 3,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;

        //Psspl: Add configuration table to store gateway specific values
        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_gateways_config";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_gateways_config (
            id integer unsigned NOT NULL auto_increment,
            configuration_title varchar(255) NULL default '',
            configuration_key varchar(255) NULL default '',
            configuration_value varchar(255) NULL default '',
            configuration_desc varchar(255) NULL default '',
            configuration_group_id int NOT NULL,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;
        
        // Psspl: Added the create table statement for gateway and 
        //        credit card type relation
        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_relation";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_relation (
            id integer unsigned NOT NULL auto_increment,
            gateway_id int NOT NULL,
            paymentmethod_id int NOT NULL,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;
        
        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_orders";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_orders (
            `id` integer unsigned NOT NULL auto_increment,
            `payment_id` integer unsigned NOT NULL,
            `net_amount` double NOT NULL default 0,
            `currency_code` varchar(3) NOT NULL default '',
            `check` tinyint unsigned NOT NULL default 0,
            `state` int default 3,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;
        
        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_dta_types";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_dta_types (
            id               integer unsigned NOT NULL auto_increment,
            code             integer unsigned NOT NULL default 0,
            name             varchar(64) NOT NULL default '',
            template         varchar(64) NOT NULL default '',
            state            tinyint(3) NOT NULL default 3, 
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;

        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_dta";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_dta (
            id               integer unsigned NOT NULL auto_increment,
            payment_type     varchar(64) NOT NULL default '',
            iban             varchar(64) NOT NULL default '',
            bic              varchar(64) NOT NULL default '',
            address1         varchar(64) NOT NULL default '',
            address2         varchar(64) NOT NULL default '',
            address3         varchar(64) NOT NULL default '',
            address4         varchar(64) NOT NULL default '',
            post_code        varchar(64) NOT NULL default '',
            currency         varchar(64) NOT NULL default '',
            amount           decimal(15,5) NOT NULL default 0,
            reference        varchar(255) NOT NULL default '',
            reason           varchar(255) NOT NULL default '',
            sender_line1     varchar(255) NOT NULL default '',
            sender_line2     varchar(255) NOT NULL default '',
            sender_line3     varchar(255) NOT NULL default '',
            sender_line4     varchar(255) NOT NULL default '',
            sender_account   varchar(255) NOT NULL default '',
            sender_reference varchar(255) NOT NULL default '',
            transaction_date integer unsigned NOT NULL default 0,
            time_created     integer unsigned NOT NULL default 0,
            time_processed   integer unsigned NOT NULL default 0,
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;

        $query = "DROP TABLE IF EXISTS " . $prefix . "_payments_debit_account";
        if (!$q->run($query)) return;
        $query = "CREATE TABLE " . $prefix . "_payments_debit_account (
            id               integer unsigned NOT NULL auto_increment,
            name             varchar(64) NOT NULL default '',
            account          varchar(64) NOT NULL default '',
            iban             varchar(64) NOT NULL default '',
            bic              varchar(64) NOT NULL default '',
            clearing         varchar(64) NOT NULL default '',
            holder           varchar(64) NOT NULL default '',
            address1         varchar(64) NOT NULL default '',
            address2         varchar(64) NOT NULL default '',
            address3         varchar(64) NOT NULL default '',
            address4         varchar(64) NOT NULL default '',
            bank_name        varchar(64) NOT NULL default '',
            bank_short_name  varchar(64) NOT NULL default '',
            time_created     integer unsigned NOT NULL default 0,
            time_modified    integer unsigned NOT NULL default 0,
            state            tinyint(3) NOT NULL default 3, 
            PRIMARY KEY (id)
        )";
        if (!$q->run($query)) return;

        if (xarMod::isAvailable('mailer')) {
        /*
            //Psspl : Added mail template for sending OTP Re-order email.
            $dbconn = xarDB::getConn();
            $query = "Select name from " . $prefix . "_mailer_mails where name = 'OTP Re-order Email'";
            $result = $dbconn->Execute($query);   
            if ($result){ 
                $fields = $result->fields;            
                if(empty($fields)){ 
                    $query = "INSERT INTO " . $prefix . "_mailer_mails (`name`, `sendername`, `senderaddress`, `subject`, `body`, `header_id`, `paymentster_id`, `locale`, `timecreated`, `timemodified`, `role_id`, `redirect`, `redirectaddress`, `alias`, `type`, `state`) VALUES
                    ('OTP Re-order Email', '', '', 'Request to download more OTPs', 'Hello, <br/>The number of OTPs in the .ric file are less than OTP Re-order level. <br/>There are #\$OTP_count#&#160;OTPs left in the file. <br/>You are kindly requested to download more OTPs from the GestPay website. <br/>Thanks, <br/>Administrator', 0, 0, 'en_US.utf-8', 1237799929, 1237799929, 5, 0, '', 0, 4, 3)";
                    $result = $dbconn->Execute($query);
                    if (!$result) return;
                }
            }
        
            //Psspl : Added mail template to send status email of exhausted OTPs.
            $dbconn = xarDB::getConn();
            $query = "Select name from " . $prefix . "_mailer_mails where name = 'OTP Exhausted Email'";
            $result = $dbconn->Execute($query);   
            if ($result){ 
                $fields = $result->fields;            
                if(empty($fields)){ 
                    $query = "INSERT INTO " . $prefix . "_mailer_mails (`name`, `sendername`, `senderaddress`, `subject`, `body`, `header_id`, `paymentster_id`, `locale`, `timecreated`, `timemodified`, `role_id`, `redirect`, `redirectaddress`, `alias`, `type`, `state`) VALUES
                    ('OTP Exhausted Email', '', '', 'No more OTPs left to be used', 'Hello,<br/>All the available OTPs from the .ric file are exhausted. <br/>There are no more OTPs left to be used. <br/>You are kindly requested to download a fresh stock of OTPs from the GestPay website.<br/>Thanks,<br/>Administrator', 0, 0, 'en_US.utf-8', 1237888651, 1237888651, 5, 0, '', 0, 4, 3)";
                    $result = $dbconn->Execute($query);
                    if (!$result) return;
                }
            }
            */
        }
        
    # --------------------------------------------------------
    #
    # Set up masks
    #
        xarRegisterMask('ViewPayments','All','payments','All','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadPayments','All','payments','All','All','ACCESS_READ');
        xarRegisterMask('SubmitPayments','All','payments','All','All','ACCESS_COMMENT');
        xarRegisterMask('ProcessPayments','All','payments','All','All','ACCESS_MODERATE');
        xarRegisterMask('EditPayments','All','payments','All','All','ACCESS_EDIT');
        xarRegisterMask('AddPayments','All','payments','All','All','ACCESS_MODERATE');
        xarRegisterMask('ManagePayments','All','payments','All','All','ACCESS_DELETE');
        xarRegisterMask('AdminPayments','All','payments','All','All','ACCESS_ADMIN');

    # --------------------------------------------------------
    #
    # Set up privileges
    #
        xarRegisterPrivilege('SubmitPayments','All','payments','All','All','ACCESS_COMMENT');
        xarRegisterPrivilege('ProcessPayments','All','payments','All','All','ACCESS_MODERATE');
        xarRegisterPrivilege('ManagePayments','All','payments','All','All','ACCESS_DELETE');
        xarRegisterPrivilege('AdminPayments','All','payments','All','All','ACCESS_ADMIN');

    # --------------------------------------------------------
    #
    # Set up modvars
    #
        xarModVars::set('payments', 'items_per_page', 20);
        xarModVars::set('payments', 'use_module_alias',0);
        xarModVars::set('payments', 'module_alias_name','Payments');

        xarModVars::set('payments', 'gateway', '');
        xarModVars::set('payments', 'defaultcurrency', 'EUR');
        xarModVars::set('payments', 'defaultamount', 0.00);
        xarModVars::set('payments', 'customerobject', 0);
        xarModVars::set('payments', 'productobject', '');
        xarModVars::set('payments', 'orderobject', 'payments_order');
        xarModVars::set('payments', 'defaultmastertable', 'payments_paymentmethods');
        xarModVars::set('payments', 'runpayments', 1);
        xarModVars::set('payments', 'savetodb', 0);
        xarModVars::set('payments', 'alertemail', 0);
        xarModVars::set('payments', 'alertemailaddr', xarModVars::get('mail','adminmail'));
        xarModVars::set('payments', 'process', 0);
        xarModVars::set('payments', 'allowanonpay', 0);
        xarModVars::set('payments', 'payments_active', 1);
        xarModVars::set('payments', 'enable_demomode', 0);
        xarModVars::set('payments', 'demousers', serialize(array()));
        xarModVars::set('payments', 'message_id', 0);                   // Used to generate a unique ID for ebanking messages
        xarModVars::set('payments', 'message_prefix', 'Payment');       // Used to generate a unique ID for ebanking messages
    # --------------------------------------------------------
    #
    # Create DD objects
    #
        sys::import('modules.dynamicdata.class.properties.registration');
        PropertyRegistration::importPropertyTypes(false, $dirs = array('modules/payments/xarproperties'));
        $module = 'payments';
        $objects = array(
                         'payments_gateways',
                         'payments_paymentmethods',
                         'payments_ccpayments',
                         'payments_gateways_config',
                         'payments_order',
                         'payments_relation',
                         'payments_dta_types',
                         'payments_dta',
                         'payments_debit_account',
                         );
        if(!xarModAPIFunc('modules','admin','standardinstall',array('module' => $module, 'objects' => $objects))) return;

    # --------------------------------------------------------
    #
    # Set up hooks
    #

//        xarModAPIFunc('modules', 'admin', 'enablehooks',
//            array('callerModName' => 'payments', 'hookModName' => 'payments'));

        return true;
    }

    function payments_upgrade()
    {
        return true;
    }

    function payments_delete()
    {
        // Only change the next line. No need for anything else
        $this_module = 'payments';

    # --------------------------------------------------------
    #
    # Remove database tables
    #
        // Load table maintenance API
        sys::import('xaraya.tableddl');

        // Generate the SQL to drop the table using the API
        $prefix = xarDB::getPrefix();
        $table = $prefix . "_" . $this_module;
        $query = xarDBDropTable($table);
        if (empty($query)) return; // throw back

    # --------------------------------------------------------
    #
    # Delete all DD objects created by this module
    #
        try {
            $dd_objects = unserialize(xarModVars::get($this_module,$this_module . '_objects'));
            foreach ($dd_objects as $key => $value)
                $result = xarModAPIFunc('dynamicdata','admin','deleteobject',array('objectid' => $value));
        } catch (Exception $e) {}

    # --------------------------------------------------------
    #
    # Remove the categories
    #
        try {
            xarModAPIFunc('categories', 'admin', 'deletecat',
                                 array('cid' => xarModVars::get($this_module, 'basecategory'))
                                );
        } catch (Exception $e) {}

    # --------------------------------------------------------
    #
    # Remove modvars, masks and privilege instances
    #
        xarRemoveMasks($this_module);
        xarRemoveInstances($this_module);
        xarModVars::delete_all($this_module);

        return true;
    }

?>

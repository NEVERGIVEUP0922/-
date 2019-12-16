--复制全部语句在数据库执行表创建

--开始创建数据表

--ERP数据变动事件记录表
if object_id(N'e_action_log',N'U') IS NOT NULL
DROP TABLE [dbo].[e_action_log]
GO

CREATE TABLE [dbo].[e_action_log] (
[act_no] bigint IDENTITY(1,1) NOT NULL,
[act_category] varchar(50) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[act_table] varchar(50) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[act_type] varchar(10) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[act_status] tinyint DEFAULT ((0)) NOT NULL,
[create_at] datetime NULL,
[update_at] datetime NULL,
[act_pkValue] varchar(255) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
CONSTRAINT [PK__e_action_log__36297463] PRIMARY KEY CLUSTERED ([act_no])
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = OFF, ALLOW_PAGE_LOCKS = OFF)
ON [PRIMARY]
)
ON [PRIMARY]
GO

EXEC sp_addextendedproperty
'MS_Description', N'通知商城成功为1  未发送为0',
'SCHEMA', N'dbo',
'TABLE', N'e_action_log',
'COLUMN', N'act_status'

--商城订单关联ERP订单详情信息
if object_id(N'e_order_detail',N'U') IS NOT NULL
DROP TABLE [dbo].[e_order_detail]
GO
CREATE TABLE [dbo].[e_order_detail] (
[id] int IDENTITY(1,1) NOT NULL,
[order_no] varchar(50) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[erp_fsdno] varchar(50) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[erp_fthno] varchar(500) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[order_pay_code] varchar(10) COLLATE Chinese_PRC_CI_AS DEFAULT ('0') NOT NULL,
[order_pay_name] varchar(100) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[order_shipvia_code] varchar(10) COLLATE Chinese_PRC_CI_AS DEFAULT ('0') NOT NULL,
[order_shipvia_name] varchar(100) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[create_at] datetime NOT NULL,
[update_at] datetime NULL,
[erp_sdoutno] varchar(500) COLLATE Chinese_PRC_CI_AS DEFAULT '' NULL,
CONSTRAINT [PK__e_order_detail__289A6F1B] PRIMARY KEY CLUSTERED ([id])
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)
ON [PRIMARY],
CONSTRAINT [UQ__e_order_detail__298E9354] UNIQUE NONCLUSTERED ([order_no] ASC)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)
ON [PRIMARY]
)
ON [PRIMARY]
GO

--ERP分批出货纪录表
if object_id(N'e_order_part_log',N'U') IS NOT NULL
DROP TABLE [dbo].[e_order_part_log]
GO
CREATE TABLE [dbo].[e_order_part_log] (
[order_no] varchar(50) COLLATE Chinese_PRC_CI_AS NOT NULL,
[fthno] varchar(50) COLLATE Chinese_PRC_CI_AS NOT NULL,
[sync_time] datetime NULL,
[sync_status] tinyint NOT NULL,
[fsdoutno] varchar(255) COLLATE Chinese_PRC_CI_AS NULL
)
ON [PRIMARY]
GO

EXEC sp_addextendedproperty
'MS_Description', N'同步商城订单编号',
'SCHEMA', N'dbo',
'TABLE', N'e_order_part_log',
'COLUMN', N'order_no'
GO

EXEC sp_addextendedproperty
'MS_Description', N'提货单号',
'SCHEMA', N'dbo',
'TABLE', N'e_order_part_log',
'COLUMN', N'fthno'
GO

EXEC sp_addextendedproperty
'MS_Description', N'同步时间',
'SCHEMA', N'dbo',
'TABLE', N'e_order_part_log',
'COLUMN', N'sync_time'
GO

EXEC sp_addextendedproperty
'MS_Description', N'同步状态,默认0为不通知商城的  1为同步',
'SCHEMA', N'dbo',
'TABLE', N'e_order_part_log',
'COLUMN', N'sync_status'
GO

EXEC sp_addextendedproperty
'MS_Description', N'发货单号',
'SCHEMA', N'dbo',
'TABLE', N'e_order_part_log',
'COLUMN', N'fsdoutno'
GO


--商城退款订单关联ERP退款订单纪录表
if object_id(N'e_retreat_order',N'U') IS NOT NULL
DROP TABLE [dbo].[e_retreat_order]
GO
CREATE TABLE [dbo].[e_retreat_order] (
[re_sn] varchar(50) COLLATE Chinese_PRC_CI_AS DEFAULT '' NOT NULL,
[erp_fsrno] varchar(255) COLLATE Chinese_PRC_CI_AS DEFAULT '' NULL,
[create_at] datetime NOT NULL,
[update_at] datetime NULL
)
ON [PRIMARY]
GO

EXEC sp_addextendedproperty
'MS_Description', N'退货申请流水号',
'SCHEMA', N'dbo',
'TABLE', N'e_retreat_order',
'COLUMN', N're_sn'
GO

EXEC sp_addextendedproperty
'MS_Description', N'ERP退货申请订单号',
'SCHEMA', N'dbo',
'TABLE', N'e_retreat_order',
'COLUMN', N'erp_fsrno'
GO


--对接应用令牌TOKEN纪录
if object_id(N'e_token',N'U') IS NOT NULL
DROP TABLE [dbo].[e_token]
GO
CREATE TABLE [dbo].[e_token] (
[id] int NOT NULL,
[app_key] varchar(100) COLLATE Chinese_PRC_CI_AS NOT NULL,
[app_secret] varchar(100) COLLATE Chinese_PRC_CI_AS NOT NULL,
[access_token] varchar(255) COLLATE Chinese_PRC_CI_AS NULL,
[expires_in] varchar(20) COLLATE Chinese_PRC_CI_AS NOT NULL,
[create_time] int NOT NULL,
CONSTRAINT [PK__e_token__6113D268] PRIMARY KEY CLUSTERED ([id])
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)
ON [PRIMARY]
)
ON [PRIMARY]
GO

--插入纪录
INSERT INTO [e_token]([id], [app_key], [app_secret], [access_token], [expires_in], [create_time]) VALUES (1, 'shop', '4e81e466892fac9f67bedf9667501a3a', '03ce4fb36c8a7dc8d8a278aa4319fd97', '7200', 1516096652);
GO

--创建Http请求的存储过程
if EXISTS (SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[P_GET_HttpRequestData]') AND OBJECTPROPERTY(id, N'IsProcedure') = 1)
DROP PROCEDURE [dbo].[P_GET_HttpRequestData];
GO
CREATE PROCEDURE P_GET_HttpRequestData( @URL varchar(500),@status int=0 OUT )
AS
BEGIN
    DECLARE @object int,
            @errSrc int
    /*初始化对*/
    EXEC @status = SP_OACreate 'Msxml2.ServerXMLHTTP.3.0', @object OUT;
    IF @status <> 0
    BEGIN
        EXEC SP_OAGetErrorInfo @object, @errSrc OUT
        RETURN
    END
    /*创建链接*/
    EXEC @status= SP_OAMethod @object,'open',NULL,'GET',@URL
    IF @status <> 0
    BEGIN
        EXEC SP_OAGetErrorInfo @object, @errSrc OUT
        RETURN
    END
    EXEC @status=SP_OAMethod @object,'setRequestHeader','Content-Type','application/x-www-form-urlencoded'
    /*发起请求*/
    EXEC @status= SP_OAMethod @object,'send',NULL
    IF @status <> 0
    BEGIN
        EXEC SP_OAGetErrorInfo @object, @errSrc OUT
        RETURN
    END
END;
GO

--创建触发器
--客户信息变更
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[custinfor_change_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[custinfor_change_1];
GO
CREATE TRIGGER [dbo].[custinfor_change_1]
ON [dbo].[t_custinfor]
WITH EXECUTE AS CALLER
FOR UPDATE
AS
BEGIN
  declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
      set @tab='t_custinfor';
      set @act = '';
      set @lasts= '';

  --新增
  if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
      begin
          set @act='insert'
      end

  --删除
  if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
      begin
          set @act='delete'
      end

  --更新
  if( exists(select 1 from inserted) and exists(select 1 from deleted) )
      begin
          set @act='update';
      end

  if( @act <> '' )
  begin
      SELECT @minId=max(act_no) from e_action_log;
      if( @act = 'update' OR  @act = 'insert' )
          begin
              INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fcustno,@tab,'custinfor_info',GETDATE(),@act from Inserted
          end
      if( @act = 'delete' )
          begin
              INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fcustno,@tab,'custinfor_info',GETDATE(),@act from Deleted
          end
      SELECT @maxId=max(act_no) from e_action_log;

      if( @maxId > @minId+1 )
          begin
              select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
          end
      else if( @maxId < @minId+1 )
          begin
              select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
          end
      else
          begin
              set @lasts=@maxId;
          end
      set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
      EXECUTE P_GET_HttpRequestData @url;
  end
END;
GO

--客户欠款变动
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[custinfot_debt_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[custinfor_debt_1];
GO
CREATE TRIGGER [dbo].[custinfor_debt_1]
ON [dbo].[t_ar001]
WITH EXECUTE AS CALLER
FOR INSERT, UPDATE
AS
BEGIN
    declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
        set @tab='t_ar001';
        set @act = '';
        set @lasts= '';

    --新增
    if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
        begin
            set @act='insert'
        end

    -- 	--删除
    -- 		if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
    -- 			begin
    -- 				set @act='delete'
    -- 			end

    --更新
    if( exists(select 1 from inserted) and exists(select 1 from deleted) )
        begin
            set @act='update';
        end

    if( @act <> '' )
    begin
        SELECT @minId=max(act_no) from e_action_log;
        if( @act = 'update' OR  @act = 'insert' )
            begin
                INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.forgno,@tab,'custinfor_debt',GETDATE(),@act from Inserted
            end
        if( @act = 'delete' )
            begin
                INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.forgno,@tab,'custinfor_debt',GETDATE(),@act from Deleted
            end
        SELECT @maxId=max(act_no) from e_action_log;

        if( @maxId > @minId+1 )
			begin
				select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
			end
        else if( @maxId < @minId+1 )
			begin
				select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
			end
        else
			begin
				set @lasts=@maxId;
			end
        set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
        EXECUTE P_GET_HttpRequestData @url;
    end
END

--系统用户信息变更
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[empl_info_change]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[empl_info_change];
GO
CREATE TRIGGER [dbo].[empl_info_change]
ON [dbo].[t_hrempl]
WITH EXECUTE AS CALLER
FOR INSERT, UPDATE, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_hrempl';
		set @act = '';
		set @lasts= '';

	--新增
	if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
		begin
			set @act='insert'
		end

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='delete'
		end

	--更新
	if( exists(select 1 from inserted) and exists(select 1 from deleted) )
	begin
		if( update(femplname) )
		begin
			set @act='update';
		end
	end

	if( @act <> '' )
	begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'update' OR  @act = 'insert' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.femplno,@tab,'empl_info',GETDATE(),@act from Inserted
			end
		if( @act = 'delete' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.femplno,@tab,'empl_info',GETDATE(),@act from Deleted
			end
		SELECT @maxId=max(act_no) from e_action_log;

		if( @maxId > @minId+1 )
			begin
				select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
			end
		else if( @maxId < @minId+1 )
			begin
				select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
			end
		else
			begin
				set @lasts=@maxId;
			end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO
--订单付款变动
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[order_credit_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[order_credit_1];
GO
CREATE TRIGGER [dbo].[order_credit_1]
ON [dbo].[t_ar001]
WITH EXECUTE AS CALLER
FOR UPDATE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_ar001';
		set @act = '';
		set @lasts= '';

	--新增
	-- 		if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
	-- 			begin
	-- 				set @act='insert'
	-- 			end

	-- 	--删除
	-- 		if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
	-- 			begin
	-- 				set @act='delete'
	-- 			end

	--更新
	if( update(fyjdate) )
	begin
		set @act='update';
	end

	if( @act <> '' )
	begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'update')
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.finno,@tab,'order_credit',GETDATE(),@act from Inserted
			end
		SELECT @maxId=max(act_no) from e_action_log;

		if( @maxId > @minId+1 )
			begin
				select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
			end
		else if( @maxId < @minId+1 )
			begin
				select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
			end
		else
			begin
				set @lasts=@maxId;
			end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO

--订单货运信息
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[order_hyInfo_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[order_hyInfo_1];
GO
CREATE TRIGGER [dbo].[order_hyInfo_1]
ON [dbo].[t_SDTH]
WITH EXECUTE AS CALLER
FOR UPDATE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_sdth';
		set @act = '';
		set @lasts= '';

	--更新
	if( update(fstatus) or update(fhycompanyname) or update(fhycontactor) or update(fhytel)  or update(fhyno) or update(fhynote) or update(fhydate) or update(fhyetd))
		BEGIN
			set @act='update';
		END

	if( @act <> '' )
	begin
	SELECT @minId=max(act_no) from e_action_log;
	if( @act = 'update' OR  @act = 'insert' )
		begin
			INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fthno,@tab,'order_hyinfo',GETDATE(),@act from Inserted
		end
		SELECT @maxId=max(act_no) from e_action_log;

	if( @maxId > @minId+1 )
		begin
			select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
		end
	else if( @maxId < @minId+1 )
		begin
			select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
		end
	else
		begin
			set @lasts=@maxId;
		end
	set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
	EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO

--订单发货完成
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[order_sdoutcomplate_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[order_sdoutcomplate_1];
GO
CREATE TRIGGER [dbo].[order_sdoutcomplate_1]
ON [dbo].[t_Invsdout]
WITH EXECUTE AS CALLER
FOR UPDATE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_Invsdout';
		set @act = '';
		set @lasts= '';

	--更新
	if( exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='update';
		end

	if( @act <> '' )
		begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'update' OR  @act = 'insert' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fsdoutno,@tab,'order_sdoutcomplate',GETDATE(),@act from Inserted
			end
			SELECT @maxId=max(act_no) from e_action_log;

			if( @maxId > @minId+1 )
				begin
					select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
				end
			else if( @maxId < @minId+1 )
				begin
					select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
				end
			else
				begin
					set @lasts=@maxId;
				end
			set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
			EXECUTE P_GET_HttpRequestData @url;
		end
END;
GO

--订单产品信息变更
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[productInfo_change_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[productInfo_change_1];
GO
CREATE TRIGGER [dbo].[productInfo_change_1]
ON [dbo].[t_item]
WITH EXECUTE AS CALLER
FOR INSERT, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
			set @tab='t_item';
			set @act = '';
			set @lasts= '';

	--新增
	if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
		begin
			set @act='insert'
		end

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='delete'
		end

	--更新
	--if( exists(select 1 from inserted) and exists(select 1 from deleted) )
	--BEGIN
	--if( update(fdelete) )
	--BEGIN
	--set @act='update';
	--END
	--END

	if( @act <> '' )
		begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'insert' )
			begin
			I	NSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'productInfo_change',GETDATE(),@act from Inserted
			end
		if( @act = 'delete' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fitemno,@tab,'productInfo_delete',GETDATE(),@act from Deleted
			end
		SELECT @maxId=max(act_no) from e_action_log;

		if( @maxId > @minId+1 )
		begin
		select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
		end
		else if( @maxId < @minId+1 )
		begin
			select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
		end
		else
			begin
				set @lasts=@maxId;
			end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
		end
END;
GO


--产品价格变更
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[product_price]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[product_price];
GO
CREATE TRIGGER [dbo].[product_price]
ON [dbo].[t_itemstcost]
WITH EXECUTE AS CALLER
FOR INSERT, UPDATE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
			set @tab='t_itemstcost';
			set @act = '';
			set @lasts= '';

	--新增
	if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
		begin
			set @act='insert'
		end
	--更新
	if( update(fstcb) )
		begin
			set @act='update';
		end

	if( @act <> '' )
	begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'update' OR  @act = 'insert' )
		begin
			INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'product_price',GETDATE(),@act from Inserted
		end
	SELECT @maxId=max(act_no) from e_action_log;

	if( @maxId > @minId+1 )
		begin
			select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
		end
		else if( @maxId < @minId+1 )
		begin
			select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
		end
	else
		begin
			set @lasts=@maxId;
		end
	set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
	EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO;


--产品数量变更
if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[product_qty_1]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[product_qty_1];
GO
CREATE TRIGGER [dbo].[product_qty_1]
ON [dbo].[t_WHItem]
WITH EXECUTE AS CALLER
FOR UPDATE, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_WHItem';
		set @act = '';
		set @lasts= '';

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='delete'
		end
	--更新
	if( update(fqty) )
		begin
			set @act='update';
		end

	if( @act <> '' )
	begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'update' OR  @act = 'insert' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'product_qty',GETDATE(),@act from Inserted
			end
		if( @act = 'delete' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fitemno,@tab,'product_qty',GETDATE(),@act from Deleted
			end
		SELECT @maxId=max(act_no) from e_action_log;

		if( @maxId > @minId+1 )
			begin
				select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
			end
		else if( @maxId < @minId+1 )
			begin
				select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
			end
		else
			begin
				set @lasts=@maxId;
			end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO

if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[product_qty_2]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[product_qty_2];
GO
CREATE TRIGGER [dbo].[product_qty_2]
ON [dbo].[t_WhKeepEntry]
WITH EXECUTE AS CALLER
FOR UPDATE, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
	set @tab='t_WhKeepEntry';
	set @act = '';
	set @lasts= '';

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
	begin
	set @act='delete'
	end
	--更新
	if( update(fqty) OR update(ffreeqty) )
	BEGIN
	set @act='update';
	END

	if( @act <> '' )
	begin
	SELECT @minId=max(act_no) from e_action_log;
	if( @act = 'update' OR  @act = 'insert' )
		begin
			INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'product_qty',GETDATE(),@act from Inserted
		end
	if( @act = 'delete' )
		begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fitemno,@tab,'product_qty',GETDATE(),@act from Deleted
		end

	SELECT @maxId=max(act_no) from e_action_log;

	if( @maxId > @minId+1 )
		begin
		select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
		end
	else if( @maxId < @minId+1 )
		begin
		select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
		end
	else
		begin
			set @lasts=@maxId;
		end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO

if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[product_qty_3]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[product_qty_3];
GO
CREATE TRIGGER [dbo].[product_qty_3]
ON [dbo].[t_SDOrderEntry]
WITH EXECUTE AS CALLER
FOR INSERT, UPDATE, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_SDOrderEntry';
		set @act = '';
		set @lasts= '';

	--新增
	if(exists(select 1 from inserted) and not exists(select 1 from deleted) )
		begin
			set @act='insert'
		end

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='delete'
		end
	--更新
	if( update(fqty) OR update(fthqty) OR update(fstockqty) OR update(fcancelqty) )
		begin
			set @act='update';
		end

	if( @act <> '' )
		begin
			SELECT @minId=max(act_no) from e_action_log;
			if( @act = 'update' OR  @act = 'insert' )
				begin
					INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'product_qty',GETDATE(),@act from Inserted
				end
			if( @act = 'delete' )
				begin
					INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fitemno,@tab,'product_qty',GETDATE(),@act from Deleted
				end
			SELECT @maxId=max(act_no) from e_action_log;

			if( @maxId > @minId+1 )
				begin
					select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
				end
			else if( @maxId < @minId+1 )
				begin
					select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
				end
			else
				begin
					set @lasts=@maxId;
				end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
		end
END;
GO

if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[product_qty_4]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[product_qty_4];
GO
CREATE TRIGGER [dbo].[product_qty_4]
ON [dbo].[t_PurOrderEntry]
WITH EXECUTE AS CALLER
FOR UPDATE, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_PurOrderEntry';
		set @act = '';
		set @lasts= '';
	--更新
	if( update(fqty) OR update(fstockqty) OR update(fcancelqty) )
		begin
			set @act='update';
		end

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='delete'
		end

	if( @act <> '' )
	begin
		SELECT @minId=max(act_no) from e_action_log;
		if( @act = 'update' OR  @act = 'insert' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'product_qty',GETDATE(),@act from Inserted
			end
		if( @act = 'delete' )
			begin
				INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fitemno,@tab,'product_qty',GETDATE(),@act from Deleted
			end
		SELECT @maxId=max(act_no) from e_action_log;
		if( @maxId > @minId+1 )
			begin
				select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
			end
		else if( @maxId < @minId+1 )
			begin
				select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
			end
		else
			begin
			set @lasts=@maxId;
			end
		set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
		EXECUTE P_GET_HttpRequestData @url;
	end
END;
GO

if EXISTS(SELECT * FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[product_qty_5]') AND OBJECTPROPERTY(id, N'IsTrigger') = 1)
DROP trigger [dbo].[product_qty_5];
GO
CREATE TRIGGER [dbo].[product_qty_5]
ON [dbo].[t_PROOEMEntry]
WITH EXECUTE AS CALLER
FOR UPDATE, DELETE
AS
BEGIN
	declare @tab varchar(100), @url varchar(4000),@act varchar(20),@lasts varchar(4000),@minId int,@maxId int;
		set @tab='t_PROOEMEntry';
		set @act = '';
		set @lasts= '';

	--删除
	if(not exists(select 1 from inserted) and exists(select 1 from deleted) )
		begin
			set @act='delete'
		end
	--更新
	if( update(fqty) OR update(fstockqty) )
		begin
			set @act='update';
		end

	if( @act <> '' )
		begin
			SELECT @minId=max(act_no) from e_action_log;
			if( @act = 'update' OR  @act = 'insert' )
				begin
					INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Inserted.fitemno,@tab,'product_qty',GETDATE(),@act from Inserted
				end
			if( @act = 'delete' )
				begin
					INSERT INTO e_action_log( act_pkValue, act_table, act_category, create_at, act_type) SELECT Deleted.fitemno,@tab,'product_qty',GETDATE(),@act from Deleted
				end
			SELECT @maxId=max(act_no) from e_action_log;

			if( @maxId > @minId+1 )
				begin
					select @lasts=stuff((select ','+convert(varchar(20),act_no) from e_action_log where act_no between @minId+1 and @maxId for xml path ('')),1,1,'')
				end
			else if( @maxId < @minId+1 )
				begin
					select @lasts=stuff((select ','+ convert(varchar(20),act_no) from e_action_log where act_no between @maxId+1 and @minId for xml path ('')),1,1,'')
				end
			else
				begin
					set @lasts=@maxId;
				end
			set @url='http://192.168.5.44/notice.php?act_no='+@lasts;
			EXECUTE P_GET_HttpRequestData @url;
		end
END;
GO
